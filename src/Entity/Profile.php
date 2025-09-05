<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicturePath = null;

    #[ORM\Column(length: 48)]
    private ?string $displayName = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $pronouns = null;

    #[ORM\Column(length: 32)]
    private ?string $job = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 98, nullable: true)]
    private ?string $websiteName = null;

    #[ORM\Column(length: 98, nullable: true)]
    private ?string $websiteLink = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Profils que ce profil suit
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    #[ORM\JoinTable(name: 'profile_follows')]
    private Collection $following;

    /**
     * Profils qui suivent ce profil
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'following')]
    private Collection $followers;

    /**
     * Posts que ce profil a likés
     */
    #[ORM\ManyToMany(targetEntity: Post::class, inversedBy: 'likedBy')]
    #[ORM\JoinTable(name: 'profile_post_likes')]
    private Collection $likedPosts;

    #[ORM\OneToOne(mappedBy: 'profile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    /**
     * @var Collection<int, ResumeSection>
     */
    #[ORM\OneToMany(targetEntity: ResumeSection::class, mappedBy: 'profile', orphanRemoval: true)]
    private Collection $resumeSections;

    public function __construct()
    {
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->likedPosts = new ArrayCollection();
        $this->resumeSections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getPronouns(): ?string
    {
        return $this->pronouns;
    }

    public function setPronouns(?string $pronouns): static
    {
        $this->pronouns = $pronouns;

        return $this;
    }

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(string $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

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

    /**
     * @return Collection<int, Profile>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(Profile $following): static
    {
        if (!$this->following->contains($following)) {
            $this->following->add($following);
        }

        return $this;
    }

    public function removeFollowing(Profile $following): static
    {
        $this->following->removeElement($following);

        return $this;
    }

    /**
     * @return Collection<int, Profile>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(Profile $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            $follower->addFollowing($this);
        }

        return $this;
    }

    public function removeFollower(Profile $follower): static
    {
        if ($this->followers->removeElement($follower)) {
            $follower->removeFollowing($this);
        }

        return $this;
    }

    /**
     * Vérifie si ce profil suit un autre profil
     */
    public function isFollowing(Profile $profile): bool
    {
        return $this->following->contains($profile);
    }

    /**
     * Vérifie si ce profil est suivi par un autre profil
     */
    public function isFollowedBy(Profile $profile): bool
    {
        return $this->followers->contains($profile);
    }

    /**
     * Suit un autre profil
     */
    public function follow(Profile $profile): static
    {
        if (!$this->isFollowing($profile) && $profile !== $this) {
            $this->addFollowing($profile);
            $profile->addFollower($this);
        }

        return $this;
    }

    /**
     * Arrête de suivre un profil
     */
    public function unfollow(Profile $profile): static
    {
        if ($this->isFollowing($profile)) {
            $this->removeFollowing($profile);
            $profile->removeFollower($this);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        // set the owning side of the relation if necessary
        if ($user->getProfile() !== $this) {
            $user->setProfile($this);
        }

        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getLikedPosts(): Collection
    {
        return $this->likedPosts;
    }

    public function addLikedPost(Post $likedPost): static
    {
        if (!$this->likedPosts->contains($likedPost)) {
            $this->likedPosts->add($likedPost);
        }

        return $this;
    }

    public function removeLikedPost(Post $likedPost): static
    {
        $this->likedPosts->removeElement($likedPost);

        return $this;
    }

    /**
     * Vérifie si ce profil a liké un post
     */
    public function hasLikedPost(Post $post): bool
    {
        return $this->likedPosts->contains($post);
    }

    /**
     * Like un post
     */
    public function likePost(Post $post): static
    {
        if (!$this->hasLikedPost($post)) {
            $this->addLikedPost($post);
            $post->addLikedBy($this);
        }

        return $this;
    }

    /**
     * Unlike un post
     */
    public function unlikePost(Post $post): static
    {
        if ($this->hasLikedPost($post)) {
            $this->removeLikedPost($post);
            $post->removeLikedBy($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, ResumeSection>
     */
    public function getResumeSections(): Collection
    {
        return $this->resumeSections;
    }

    public function addResumeSection(ResumeSection $resumeSection): static
    {
        if (!$this->resumeSections->contains($resumeSection)) {
            $this->resumeSections->add($resumeSection);
            $resumeSection->setProfile($this);
        }

        return $this;
    }

    public function removeResumeSection(ResumeSection $resumeSection): static
    {
        if ($this->resumeSections->removeElement($resumeSection)) {
            // set the owning side to null (unless already changed)
            if ($resumeSection->getProfile() === $this) {
                $resumeSection->setProfile(null);
            }
        }

        return $this;
    }
}
