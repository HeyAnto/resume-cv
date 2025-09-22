<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 98)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 32)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\Column(length: 98, nullable: true)]
    private ?string $websiteName = null;

    #[ORM\Column(length: 98, nullable: true)]
    private ?string $websiteLink = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'companies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, JobOffer>
     */
    #[ORM\OneToMany(targetEntity: JobOffer::class, mappedBy: 'company', orphanRemoval: true)]
    private Collection $jobOffers;

    /**
     * @var Collection<int, CompanyTag>
     */
    #[ORM\OneToMany(targetEntity: CompanyTag::class, mappedBy: 'company', orphanRemoval: true)]
    private Collection $companyTags;

    /**
     * @var Collection<int, DefaultTag>
     */
    #[ORM\ManyToMany(targetEntity: DefaultTag::class, inversedBy: 'companies')]
    private Collection $defaultTags;

    public function __construct()
    {
        $this->jobOffers = new ArrayCollection();
        $this->companyTags = new ArrayCollection();
        $this->defaultTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getProfilePicturePath(): ?string
    {
        return $this->profilePicturePath;
    }

    public function setProfilePicturePath(?string $profilePicturePath): static
    {
        $this->profilePicturePath = $profilePicturePath;

        return $this;
    }

    public function getWebsiteName(): ?string
    {
        return $this->websiteName;
    }

    public function setWebsiteName(?string $websiteName): static
    {
        $this->websiteName = $websiteName;

        return $this;
    }

    public function getWebsiteLink(): ?string
    {
        return $this->websiteLink;
    }

    public function setWebsiteLink(?string $websiteLink): static
    {
        $this->websiteLink = $websiteLink;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobOffer $jobOffer): static
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->setCompany($this);
        }

        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): static
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            // set the owning side to null (unless already changed)
            if ($jobOffer->getCompany() === $this) {
                $jobOffer->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompanyTag>
     */
    public function getCompanyTags(): Collection
    {
        return $this->companyTags;
    }

    public function addCompanyTag(CompanyTag $companyTag): static
    {
        if (!$this->companyTags->contains($companyTag)) {
            $this->companyTags->add($companyTag);
            $companyTag->setCompany($this);
        }

        return $this;
    }

    public function removeCompanyTag(CompanyTag $companyTag): static
    {
        if ($this->companyTags->removeElement($companyTag)) {
            // set the owning side to null (unless already changed)
            if ($companyTag->getCompany() === $this) {
                $companyTag->setCompany(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DefaultTag>
     */
    public function getDefaultTags(): Collection
    {
        return $this->defaultTags;
    }

    public function addDefaultTag(DefaultTag $defaultTag): static
    {
        if (!$this->defaultTags->contains($defaultTag)) {
            $this->defaultTags->add($defaultTag);
        }

        return $this;
    }

    public function removeDefaultTag(DefaultTag $defaultTag): static
    {
        $this->defaultTags->removeElement($defaultTag);

        return $this;
    }

    public function getSlug(): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $this->companyName));
    }
}
