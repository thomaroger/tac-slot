<?php

namespace App\Service;

use App\Entity\Adherent;
use App\Entity\AuthCode;
use App\Entity\AuthLog;
use App\Entity\Reservation;
use App\Repository\AdherentRepository;
use App\Repository\AuthCodeRepository;
use App\Repository\AuthLogRepository;
use App\Repository\SlotRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class AuthService
{
    public const REQUEST_EMPTY_IDENTIFIER = 'empty_identifier';
    public const REQUEST_ADHERENT_NOT_FOUND = 'adherent_not_found';
    public const REQUEST_MISSING_EMAIL = 'missing_email';
    public const REQUEST_NOT_ALLOWED = 'not_allowed';
    public const REQUEST_RATE_LIMITED = 'rate_limited';
    public const REQUEST_CODE_SENT = 'code_sent';

    public const VERIFY_INVALID_SESSION = 'invalid_session';
    public const VERIFY_EMAIL_NOT_VERIFIED = 'email_not_verified';
    public const VERIFY_CODE_INVALID = 'code_invalid';
    public const VERIFY_RATE_LIMITED = 'rate_limited';
    public const VERIFY_SUCCESS = 'success';

    public const EMAIL_TOKEN_INVALID = 'token_invalid';
    public const EMAIL_TOKEN_VERIFIED = 'verified';

    public function __construct(
        private readonly AdherentRepository $adherentRepository,
        private readonly AuthCodeRepository $authCodeRepository,
        private readonly AuthLogRepository $authLogRepository,
        private readonly SlotRepository $slotRepository,
        private readonly EntityManagerInterface $em,
        private readonly AuthMailer $authMailer,
    ) {
    }

    /**
     * @return array{slotopened: bool, slotResa: int, slotCheckIn: int}
     */
    public function getCurrentSlotSummary(): array
    {
        $slotopened = false;
        $slotResa = 0;
        $slotCheckIn = 0;

        $slot = $this->slotRepository->findCurrentSlotWithReservations(new DateTimeImmutable());

        if (null !== $slot) {
            foreach ($slot->getReservations() as $reservation) {
                if ($reservation->isCheckedIn()) {
                    $slotCheckIn++;
                    $slotopened = true;
                }
                if ($reservation->getStatus() === Reservation::STATUS_CONFIRMED) {
                    $slotResa++;
                }
            }
        }

        return [
            'slotopened' => $slotopened,
            'slotResa' => $slotResa,
            'slotCheckIn' => $slotCheckIn,
        ];
    }

    /**
     * @return array{status: string, adherentId: int|null}
     */
    public function requestLoginCode(string $identifier, string $sessionId, ?string $ip, ?string $userAgent): array
    {
        if ($this->isCodeRequestRateLimited($ip)) {
            return ['status' => self::REQUEST_RATE_LIMITED, 'adherentId' => null];
        }

        $identifier = trim($identifier);
        if ($identifier === '') {
            $this->logEvent('CODE_REQUEST_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::REQUEST_EMPTY_IDENTIFIER, 'adherentId' => null];
        }

        $adherent = $this->adherentRepository->findOneByEmailOrLicense($identifier);
        if (!$adherent instanceof Adherent) {
            $this->logEvent('CODE_REQUEST_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::REQUEST_ADHERENT_NOT_FOUND, 'adherentId' => null];
        }
        if ($adherent->isDeleted()) {
            $this->logEvent('CODE_REQUEST_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::REQUEST_ADHERENT_NOT_FOUND, 'adherentId' => null];
        }
        if (!$adherent->getEmail()) {
            $this->logEvent('CODE_REQUEST_FAILED', $adherent, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::REQUEST_MISSING_EMAIL, 'adherentId' => null];
        }
        if (!$adherent->isCanOpenShoot()) {
            $this->logEvent('CODE_REQUEST_FAILED', $adherent, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::REQUEST_NOT_ALLOWED, 'adherentId' => null];
        }

        $existingCodes = $this->authCodeRepository->findLoginCodesForSessionAndAdherent($sessionId, $adherent);
        foreach ($existingCodes as $codeEntity) {
            $codeEntity->setUsedAt(new DateTimeImmutable());
        }

        $code = (string) random_int(100000, 999999);
        $authCode = new AuthCode();
        $authCode
            ->setAdherent($adherent)
            ->setCode($code)
            ->setSessionId($sessionId)
            ->setType('login')
            ->setExpiresAt((new DateTimeImmutable())->add(new DateInterval('PT5M')));
        $this->em->persist($authCode);

        if (
            !$adherent->isEmailVerified()
            && (
                !$adherent->getEmailVerificationToken()
                || null === $adherent->getEmailVerificationTokenExpiresAt()
                || $adherent->getEmailVerificationTokenExpiresAt() <= new DateTimeImmutable()
            )
        ) {
            $token = bin2hex(random_bytes(20));
            $adherent->setEmailVerificationToken($token);
            $adherent->setEmailVerificationTokenExpiresAt((new DateTimeImmutable())->add(new DateInterval('P1D')));
            $this->authMailer->sendFirstLoginConfirmation($adherent, $token);
        }

        $this->logEvent('CODE_REQUEST', $adherent, $ip, $userAgent);
        $this->em->flush();

        $this->authMailer->sendLoginCode($adherent, $code);

        return ['status' => self::REQUEST_CODE_SENT, 'adherentId' => $adherent->getId()];
    }

    /**
     * @return array{status: string, adherent: Adherent|null}
     */
    public function verifyLoginCode(string $code, string $sessionId, mixed $adherentId, ?string $ip, ?string $userAgent): array
    {
        if ($this->isCodeVerifyRateLimited($ip)) {
            return ['status' => self::VERIFY_RATE_LIMITED, 'adherent' => null];
        }

        $code = trim($code);

        if ($code === '' || !$adherentId) {
            $this->logEvent('LOGIN_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::VERIFY_INVALID_SESSION, 'adherent' => null];
        }

        $adherent = $this->adherentRepository->find((int) $adherentId);
        if (!$adherent instanceof Adherent) {
            $this->logEvent('LOGIN_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::VERIFY_CODE_INVALID, 'adherent' => null];
        }
        if ($adherent->isDeleted()) {
            $this->logEvent('LOGIN_FAILED', null, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::VERIFY_CODE_INVALID, 'adherent' => null];
        }

        if (!$adherent->isEmailVerified()) {
            $this->logEvent('EMAIL_NOT_VERIFIED', $adherent, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::VERIFY_EMAIL_NOT_VERIFIED, 'adherent' => null];
        }

        $authCode = $this->authCodeRepository->findOneLoginCodeForSession($sessionId, $adherent, $code);
        $now = new DateTimeImmutable();

        if ($authCode instanceof AuthCode && !$authCode->isUsed() && $authCode->getExpiresAt() > $now) {
            $authCode->setUsedAt($now);
            $this->logEvent('LOGIN_SUCCESS', $adherent, $ip, $userAgent);
            $this->em->flush();

            return ['status' => self::VERIFY_SUCCESS, 'adherent' => $adherent];
        }

        $this->logEvent('LOGIN_FAILED', null, $ip, $userAgent);
        $this->em->flush();

        return ['status' => self::VERIFY_CODE_INVALID, 'adherent' => null];
    }

    public function verifyEmailToken(string $token, ?string $ip, ?string $userAgent): string
    {
        $adherent = $this->adherentRepository->findOneByEmailVerificationToken($token);
        if (
            !$adherent instanceof Adherent
            || null === $adherent->getEmailVerificationTokenExpiresAt()
            || $adherent->getEmailVerificationTokenExpiresAt() <= new DateTimeImmutable()
        ) {
            return self::EMAIL_TOKEN_INVALID;
        }

        $adherent->setEmailVerified(true);
        $adherent->setEmailVerificationToken(null);
        $adherent->setEmailVerificationTokenExpiresAt(null);
        $this->logEvent('EMAIL_VERIFIED', $adherent, $ip, $userAgent);
        $this->em->flush();

        return self::EMAIL_TOKEN_VERIFIED;
    }

    private function logEvent(string $event, ?Adherent $adherent, ?string $ip, ?string $userAgent): void
    {
        $log = new AuthLog();
        $log->setEvent($event)
            ->setAdherent($adherent)
            ->setIp($ip)
            ->setUserAgent($userAgent);

        $this->em->persist($log);
    }

    private function isCodeRequestRateLimited(?string $ip): bool
    {
        $count = $this->authLogRepository->countRecentByIpAndEvents(
            $ip,
            ['CODE_REQUEST', 'CODE_REQUEST_FAILED'],
            new DateTimeImmutable('-15 minutes')
        );

        return $count >= 5;
    }

    private function isCodeVerifyRateLimited(?string $ip): bool
    {
        $count = $this->authLogRepository->countRecentByIpAndEvents(
            $ip,
            ['LOGIN_SUCCESS', 'LOGIN_FAILED'],
            new DateTimeImmutable('-15 minutes')
        );

        return $count >= 10;
    }
}
