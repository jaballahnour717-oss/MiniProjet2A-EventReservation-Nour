<?php

namespace App\Entity;

use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: WebauthnCredentialRepository::class)]
#[ORM\Table(name: 'webauthn_credentials')]
#[ORM\Index(columns: ['credential_id'], name: 'idx_credential_id')]
class WebauthnCredential
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

   
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

   
    #[ORM\Column(length: 512, unique: true)]
    private string $credentialId;


    #[ORM\Column(type: 'text')]
    private string $publicKey;

  
    #[ORM\Column(length: 255)]
    private string $deviceName = 'Passkey';

  
    #[ORM\Column]
    private int $signCount = 0;


    #[ORM\Column(length: 36, nullable: true)]
    private ?string $aaguid = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        User   $user,
        string $credentialId,
        string $publicKey,
        string $deviceName = 'Passkey',
        int    $signCount = 0,
        ?string $aaguid = null,
    ) {
        $this->user         = $user;
        $this->credentialId = $credentialId;
        $this->publicKey    = $publicKey;
        $this->deviceName   = $deviceName;
        $this->signCount    = $signCount;
        $this->aaguid       = $aaguid;
        $this->createdAt    = new \DateTimeImmutable();
    }

    // ── Getters ───────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getSignCount(): int
    {
        return $this->signCount;
    }

    public function getAaguid(): ?string
    {
        return $this->aaguid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

   
    public function setDeviceName(string $deviceName): static
    {
        $this->deviceName = $deviceName;
        return $this;
    }

   
    public function recordUsage(int $newSignCount): static
    {
        $this->signCount  = $newSignCount;
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'credentialId'=> $this->credentialId,
            'deviceName'  => $this->deviceName,
            'signCount'   => $this->signCount,
            'aaguid'      => $this->aaguid,
            'createdAt'   => $this->createdAt->format('Y-m-d H:i:s'),
            'lastUsedAt'  => $this->lastUsedAt?->format('Y-m-d H:i:s'),
        ];
    }
}