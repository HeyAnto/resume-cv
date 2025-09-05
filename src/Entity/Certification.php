<?php

namespace App\Entity;

use App\Repository\CertificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CertificationRepository::class)]
class Certification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 98)]
    private ?string $title = null;

    #[ORM\Column(length: 98)]
    private ?string $authority = null;

    #[ORM\Column(length: 98, nullable: true)]
    private ?string $authorityLink = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $issuedDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expirationDate = null;

    #[ORM\Column(length: 255)]
    private ?string $credentialId = null;

    #[ORM\Column(length: 255)]
    private ?string $credentialUrl = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'certifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ResumeSection $resumeSection = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthority(): ?string
    {
        return $this->authority;
    }

    public function setAuthority(string $authority): static
    {
        $this->authority = $authority;

        return $this;
    }

    public function getAuthorityLink(): ?string
    {
        return $this->authorityLink;
    }

    public function setAuthorityLink(?string $authorityLink): static
    {
        $this->authorityLink = $authorityLink;

        return $this;
    }

    public function getIssuedDate(): ?\DateTimeImmutable
    {
        return $this->issuedDate;
    }

    public function setIssuedDate(\DateTimeImmutable $issuedDate): static
    {
        $this->issuedDate = $issuedDate;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeImmutable $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getCredentialId(): ?string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): static
    {
        $this->credentialId = $credentialId;

        return $this;
    }

    public function getCredentialUrl(): ?string
    {
        return $this->credentialUrl;
    }

    public function setCredentialUrl(string $credentialUrl): static
    {
        $this->credentialUrl = $credentialUrl;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getResumeSection(): ?ResumeSection
    {
        return $this->resumeSection;
    }

    public function setResumeSection(?ResumeSection $resumeSection): static
    {
        $this->resumeSection = $resumeSection;

        return $this;
    }
}
