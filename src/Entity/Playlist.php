<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Playlist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;


    /**
     * Nombre d'écoutes de la playlist
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $playCount = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPublic = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'playlists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToMany(targetEntity: Track::class, inversedBy: 'playlists')]
    #[ORM\JoinTable(name: 'playlist_track')]
    private Collection $tracks;

    public function __construct()
    {
        $this->tracks = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getCreatedBy(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->owner = $createdBy;
        return $this;
    }

    /**
     * @return Collection<int, Track>
     */
    public function getTracks(): Collection
    {
        return $this->tracks;
    }

    public function addTrack(Track $track): static
    {
        if (!$this->tracks->contains($track)) {
            $this->tracks->add($track);
        }

        return $this;
    }

    public function removeTrack(Track $track): static
    {
        $this->tracks->removeElement($track);
        return $this;
    }


    public function getTrackCount(): int
    {
        return $this->tracks->count();
    }

    public function getPlayCount(): int
    {
        return $this->playCount;
    }

    public function setPlayCount(int $playCount): static
    {
        $this->playCount = $playCount;
        return $this;
    }

    /**
     * Incrémente le nombre d'écoutes de la playlist
     */
    public function incrementPlayCount(): static
    {
        $this->playCount++;
        return $this;
    }

    public function getTotalDuration(): int
    {
        $totalDuration = 0;
        foreach ($this->tracks as $track) {
            $totalDuration += $track->getDuration() ?? 0;
        }
        return $totalDuration;
    }

    public function getFormattedTotalDuration(): string
    {
        $totalSeconds = $this->getTotalDuration();
        if ($totalSeconds < 3600) {
            $minutes = floor($totalSeconds / 60);
            $seconds = $totalSeconds % 60;
            return sprintf('%d:%02d', $minutes, $seconds);
        } else {
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
    }

    /**
     * Get HTML for cover image or default cover
     * @param int $size Size in pixels (width and height)
     * @param string $iconClass FontAwesome icon class for default cover
     * @return string HTML string for cover display
     */
    public function getCoverHtml(int $size = 50, string $iconClass = 'fas fa-list'): string
    {
        if ($this->coverImage) {
            return sprintf(
                '<img src="/uploads/covers/%s" alt="Cover" class="rounded" width="%d" height="%d">',
                htmlspecialchars($this->coverImage),
                $size,
                $size
            );
        }

        return sprintf(
            '<div class="bg-gradient-primary rounded d-flex align-items-center justify-content-center" style="width: %dpx; height: %dpx; background: linear-gradient(45deg, #667eea, #764ba2);">
                <i class="%s text-white"></i>
            </div>',
            $size,
            $size,
            htmlspecialchars($iconClass)
        );
    }
}