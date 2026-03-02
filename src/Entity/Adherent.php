<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class Adherent implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $licenseNumber = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 30)]
    private ?string $level = null;

    #[ORM\Column(options: [
        'default' => false,
    ])]
    private bool $airKey = false;

    #[ORM\Column(length: 30)]
    private ?string $role = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'boolean', options: [
        'default' => false,
    ])]
    private bool $emailVerified = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $emailVerificationTokenExpiresAt = null;

    #[ORM\Column(type: 'boolean', options: [
        'default' => false,
    ])]
    private bool $canOpenShoot = false;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getId() . ' - ' . $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function hasAirKey(): bool
    {
        return $this->airKey;
    }

    public function setAirKey(bool $airKey): self
    {
        $this->airKey = $airKey;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): self
    {
        $this->emailVerified = $emailVerified;

        return $this;
    }

    public function isCanOpenShoot(): bool
    {
        return $this->canOpenShoot;
    }

    public function setCanOpenShoot(bool $canOpenShoot): self
    {
        $this->canOpenShoot = $canOpenShoot;

        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $token): self
    {
        $this->emailVerificationToken = $token;

        return $this;
    }

    public function getEmailVerificationTokenExpiresAt(): ?DateTimeImmutable
    {
        return $this->emailVerificationTokenExpiresAt;
    }

    public function setEmailVerificationTokenExpiresAt(?DateTimeImmutable $expiresAt): self
    {
        $this->emailVerificationTokenExpiresAt = $expiresAt;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?: (string) $this->licenseNumber;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if ($this->role === 'administrateur' || $this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {

    }
}
