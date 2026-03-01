<?php

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

    public function importCsvFile(UploadedFile $file): int
    {
        $path = $file->getRealPath();
        if (!$path) {
            return 0;
        }

        $handle = fopen($path, 'r');
        if (false === $handle) {
            return 0;
        }

        $count = 0;
        $header = null;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($header === null) {
                $header = $row;
                continue;
            }

            $data = array_combine($header, $row);
            if (!is_array($data)) {
                continue;
            }

            $licenseNumber = trim((string) ($data['numero_licence'] ?? $data['Numéro de licence'] ?? ''));
            if ($licenseNumber === '') {
                continue;
            }

            $adherent = $this->adherentRepository->findOneByLicenseNumber($licenseNumber);
            if (!$adherent instanceof Adherent) {
                $adherent = new Adherent();
                $adherent->setLicenseNumber($licenseNumber);
                $this->em->persist($adherent);
            }

            $adherent
                ->setFirstName($this->getString($data, ['prenom', 'Prénom']))
                ->setLastName($this->getString($data, ['nom', 'Nom']))
                ->setEmail($this->getString($data, ['email', 'Email']));

            $level = $this->getString($data, ['niveau', 'Niveau']);
            if (null !== $level) {
                $adherent->setLevel($level);
            }

            $airKeyRaw = $this->getString($data, ['air_key', 'Air key', 'Air Key']);
            if (null !== $airKeyRaw) {
                $adherent->setAirKey($this->toBool($airKeyRaw));
            }

            $role = $this->getString($data, ['role', 'Role', 'Rôle']);
            if (null !== $role) {
                $adherent->setRole($role);
            }

            $adherent->setUpdatedAt(new DateTimeImmutable());
            $count++;
        }

        fclose($handle);
        $this->em->flush();

        return $count;
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

        return in_array($normalized, ['1', 'true', 'oui', 'yes', 'y', 'o'], true);
    }
}
