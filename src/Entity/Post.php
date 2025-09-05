<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 280)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\Column]
    private ?bool $isVisible = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Profils qui ont liké ce post
     */
    #[ORM\ManyToMany(targetEntity: Profile::class, mappedBy: 'likedPosts')]
    private Collection $likedBy;

    public function __construct()
    {
        $this->likedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

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
     * @return Collection<int, Profile>
     */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    public function addLikedBy(Profile $likedBy): static
    {
        if (!$this->likedBy->contains($likedBy)) {
            $this->likedBy->add($likedBy);
            $likedBy->addLikedPost($this);
        }

        return $this;
    }

    public function removeLikedBy(Profile $likedBy): static
    {
        if ($this->likedBy->removeElement($likedBy)) {
            $likedBy->removeLikedPost($this);
        }

        return $this;
    }

    /**
     * Vérifie si un profil a liké ce post
     */
    public function isLikedBy(Profile $profile): bool
    {
        return $this->likedBy->contains($profile);
    }

    /**
     * Retourne le nombre de likes
     */
    public function getLikesCount(): int
    {
        return $this->likedBy->count();
    }

    /**
     * Un profil like ce post
     */
    public function likeBy(Profile $profile): static
    {
        if (!$this->isLikedBy($profile)) {
            $this->addLikedBy($profile);
        }

        return $this;
    }

    /**
     * Un profil unlike ce post
     */
    public function unlikeBy(Profile $profile): static
    {
        if ($this->isLikedBy($profile)) {
            $this->removeLikedBy($profile);
        }

        return $this;
    }
}
