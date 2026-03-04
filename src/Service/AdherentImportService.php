<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Adherent;
use App\Repository\AdherentRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdherentImportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdherentRepository $adherentRepository,
    ) {
    }

    /**
     * @return array{
     *     total_rows: int,
     *     processed: int,
     *     created: int,
     *     updated: int,
     *     ignored: int,
     *     errors: array<int, array{line: int, reason: string}>
     * }
     */
    public function importCsvFile(UploadedFile $file): array
    {
        $report = $this->emptyReport();

        $path = $file->getRealPath();
        if (! $path) {
            $report['errors'][] = [
                'line' => 0,
                'reason' => 'Impossible de lire le fichier uploadé.',
            ];

            return $report;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $report['errors'][] = [
                'line' => 0,
                'reason' => 'Ouverture du fichier impossible.',
            ];

            return $report;
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);
            $report['errors'][] = [
                'line' => 0,
                'reason' => 'Le fichier est vide.',
            ];

            return $report;
        }

        $delimiter = $this->detectDelimiter($headerLine);
        $header = str_getcsv($headerLine, $delimiter);
        $headerMap = $this->buildHeaderMap($header);
        $headerLineCount = 1;

        if ($this->isGenericColumnHeaderMap($headerMap)) {
            $nextRow = fgetcsv($handle, 0, $delimiter);
            if (is_array($nextRow)) {
                $candidateHeaderMap = $this->buildHeaderMap($nextRow);
                if ($this->isRecognizedHeaderMap($candidateHeaderMap)) {
                    $headerMap = $candidateHeaderMap;
                    $headerLineCount = 2;
                }
            }
        }

        $now = new DateTimeImmutable();
        $lineNumber = $headerLineCount;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;
            $report['total_rows']++;

            $data = $this->buildRowMap($headerMap, $row);
            if ($data === []) {
                $report['ignored']++;
                continue;
            }

            $licenseNumberRaw = $this->getString($data, [
                'numero_licence',
                'numero_de_licence',
                'numero_licence_fftir',
                'licence',
            ]);
            if (! is_string($licenseNumberRaw)) {
                $report['ignored']++;
                $report['errors'][] = [
                    'line' => $lineNumber,
                    'reason' => 'Numéro de licence manquant.',
                ];
                continue;
            }
            $licenseNumber = trim($licenseNumberRaw);
            if ($licenseNumber === '') {
                $report['ignored']++;
                $report['errors'][] = [
                    'line' => $lineNumber,
                    'reason' => 'Numéro de licence vide.',
                ];
                continue;
            }

            $adherent = $this->adherentRepository->findOneByLicenseNumber($licenseNumber);
            $isNew = ! $adherent instanceof Adherent;
            if (! $adherent instanceof Adherent) {
                $adherent = new Adherent();
                $adherent->setLicenseNumber($licenseNumber);
                $this->em->persist($adherent);
            }

            $adherent
                ->setFirstName($this->getString($data, ['prenom', 'firstname', 'first_name']))
                ->setLastName($this->getString($data, ['nom', 'lastname', 'last_name']))
                ->setEmail($this->normalizeEmail($this->getString($data, ['email', 'mail'])));

            $level = $this->getString($data, ['niveau', 'level']);
            if ($level !== null) {
                $adherent->setLevel($level);
            }

            $airKeyRaw = $this->getString($data, ['air_key', 'airkey']);
            if ($airKeyRaw !== null) {
                $adherent->setAirKey($this->toBool($airKeyRaw));
            }

            $canOpenShootRaw = $this->getString(
                $data,
                ['can_open_shoot', 'tir_libre', 'tirlibre', 'autorise_tir_libre']
            );
            if ($canOpenShootRaw !== null) {
                $adherent->setCanOpenShoot($this->toBool($canOpenShootRaw));
            }

            $emailVerifiedRaw = $this->getString($data, ['email_verified', 'actif', 'active']);
            if ($emailVerifiedRaw !== null) {
                $adherent->setEmailVerified($this->toBool($emailVerifiedRaw));
            }

            $role = $this->getString($data, ['role']);
            if ($role !== null) {
                $adherent->setRole($this->normalizeRole($role));
            }

            $deletedRaw = $this->getString($data, ['deleted', 'supprime', 'supprime_le', 'deleted_at']);
            if ($deletedRaw !== null) {
                if ($this->toBool($deletedRaw)) {
                    if (! $adherent->isDeleted()) {
                        $adherent->setDeletedAt($now);
                    }
                } else {
                    $adherent->setDeletedAt(null);
                }
            }

            $adherent->setUpdatedAt($now);
            $report['processed']++;
            if ($isNew) {
                $report['created']++;
            } else {
                $report['updated']++;
            }
        }

        fclose($handle);
        $this->em->flush();

        return $report;
    }

    /**
     * @return array{
     *     total_rows: int,
     *     processed: int,
     *     created: int,
     *     updated: int,
     *     ignored: int,
     *     errors: array<int, array{line: int, reason: string}>
     * }
     */
    private function emptyReport(): array
    {
        return [
            'total_rows' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'ignored' => 0,
            'errors' => [],
        ];
    }

    private function detectDelimiter(string $headerLine): string
    {
        if (substr_count($headerLine, ';') >= substr_count($headerLine, ',')) {
            return ';';
        }

        return ',';
    }

    /**
     * @param string[] $header
     *
     * @return array<int, string>
     */
    private function buildHeaderMap(array $header): array
    {
        $headerMap = [];
        foreach ($header as $index => $label) {
            $headerMap[$index] = $this->normalizeHeader((string) $label);
        }

        return $headerMap;
    }

    /**
     * @param array<int, string> $headerMap
     * @param string[]           $row
     *
     * @return array<string, string>
     */
    private function buildRowMap(array $headerMap, array $row): array
    {
        $data = [];
        foreach ($headerMap as $index => $normalizedKey) {
            if ($normalizedKey === '') {
                continue;
            }

            $data[$normalizedKey] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $data;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = trim($header);
        $header = mb_strtolower($header);
        $header = strtr($header, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ÿ' => 'y',
            '\'' => '',
        ]);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    /**
     * @param array<string, string> $data
     * @param string[]              $keys
     */
    private function getString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '') {
                return (string) $data[$key];
            }
        }

        return null;
    }

    private function toBool(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'true', 'vrai', 'oui', 'yes', 'y', 'o', 'active', 'actif'], true);
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);
        if ($email === '') {
            return null;
        }

        return strtolower($email);
    }

    private function normalizeRole(string $role): string
    {
        $normalized = strtolower(trim($role));

        return match ($normalized) {
            'admin', 'administrateur', 'administrator' => 'administrateur',
            default => 'utilisateur',
        };
    }

    /**
     * @param array<int, string> $headerMap
     */
    private function isGenericColumnHeaderMap(array $headerMap): bool
    {
        if ($headerMap === []) {
            return false;
        }

        foreach ($headerMap as $value) {
            if (! preg_match('/^column_?\d+$/', $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $headerMap
     */
    private function isRecognizedHeaderMap(array $headerMap): bool
    {
        $recognized = [
            'numero_licence',
            'numero_de_licence',
            'numero_licence_fftir',
            'licence',
            'prenom',
            'nom',
            'email',
            'niveau',
            'air_key',
            'tir_libre',
            'actif',
            'role',
            'supprime',
        ];

        foreach ($headerMap as $value) {
            if (in_array($value, $recognized, true)) {
                return true;
            }
        }

        return false;
    }
}
