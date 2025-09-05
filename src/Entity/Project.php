<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 98)]
    private ?string $projectName = null;

    #[ORM\Column(length: 98)]
    private ?string $projectLink = null;

    #[ORM\Column(length: 160)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath3 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ResumeSection $resumeSection = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getProjectLink(): ?string
    {
        return $this->projectLink;
    }

    public function setProjectLink(string $projectLink): static
    {
        $this->projectLink = $projectLink;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    public function getImagePath2(): ?string
    {
        return $this->imagePath2;
    }

    public function setImagePath2(?string $imagePath2): static
    {
        $this->imagePath2 = $imagePath2;

        return $this;
    }

    public function getImagePath3(): ?string
    {
        return $this->imagePath3;
    }

    public function setImagePath3(?string $imagePath3): static
    {
        $this->imagePath3 = $imagePath3;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

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
