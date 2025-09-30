<?php

namespace App\Entity;

use App\Repository\TrackRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Track Entity - Represents a music track in the Onzeer streaming platform
 * 
 * This entity stores all information about uploaded music tracks including
 * metadata, file paths, play statistics, and relationships to playlists.
 */
#[ORM\Entity(repositoryClass: TrackRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Track
{
    /**
     * Unique identifier for the track
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Track title/name
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private ?string $title = null;

    /**
     * Artist or band name
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    private ?string $artist = null;

    /**
     * Album name (optional)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $album = null;

    /**
     * Track description or notes
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Track duration in seconds
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $duration = null;

    /**
     * Audio file path relative to uploads directory
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $audioFile = null;

    /**
     * Cover image file path relative to uploads directory
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    /**
     * Music genre (Pop, Rock, Electronic, etc.)
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    /**
     * Number of times this track has been played
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $playCount = 0;

    /**
     * Track creation timestamp
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Track last update timestamp
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * User who uploaded this track
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tracks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $uploadedBy = null;

    /**
     * Playlists that contain this track (Many-to-Many relationship)
     */
    #[ORM\ManyToMany(targetEntity: Playlist::class, mappedBy: 'tracks')]
    private Collection $playlists;

    /**
     * Constructor - Initialize collections and timestamps
     */
    public function __construct()
    {
        $this->playlists = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // ========== GETTERS AND SETTERS ==========

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

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;
        return $this;
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album): static
    {
        $this->album = $album;
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

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getAudioFile(): ?string
    {
        return $this->audioFile;
    }

    public function setAudioFile(?string $audioFile): static
    {
        $this->audioFile = $audioFile;
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

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;
        return $this;
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
     * Increment the play count by 1 (used when track is played)
     */
    public function incrementPlayCount(): static
    {
        $this->playCount++;
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

    /**
     * Doctrine lifecycle callback - automatically update timestamp on entity update
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    // ========== PLAYLIST RELATIONSHIPS ==========

    /**
     * Get all playlists containing this track
     * @return Collection<int, Playlist>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    /**
     * Add this track to a playlist
     */
    public function addPlaylist(Playlist $playlist): static
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->addTrack($this);
        }

        return $this;
    }

    /**
     * Remove this track from a playlist
     */
    public function removePlaylist(Playlist $playlist): static
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeTrack($this);
        }

        return $this;
    }

    // ========== UTILITY METHODS ==========

    /**
     * Format duration from seconds to MM:SS format
     * @return string Formatted duration (e.g., "3:45", "12:30")
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return '0:00';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get HTML for cover image or default cover
     * @param int $size Size in pixels (width and height)
     * @param string $iconClass FontAwesome icon class for default cover
     * @return string HTML string for cover display
     */
    public function getCoverHtml(int $size = 50, string $iconClass = 'fas fa-music'): string
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