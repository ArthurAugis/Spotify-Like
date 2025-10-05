<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
#[ORM\Table(name: 'recommendation')]
#[ORM\Index(columns: ['user_id'], name: 'idx_recommendation_user')]
#[ORM\Index(columns: ['recommended_track_id'], name: 'idx_recommendation_track')]
#[ORM\Index(columns: ['created_at'], name: 'idx_recommendation_created')]
class Recommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Track $recommendedTrack = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2)]
    private ?string $score = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $viewed = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $liked = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $dismissed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRecommendedTrack(): ?Track
    {
        return $this->recommendedTrack;
    }

    public function setRecommendedTrack(?Track $recommendedTrack): static
    {
        $this->recommendedTrack = $recommendedTrack;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(string $score): static
    {
        $this->score = $score;
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

    public function isViewed(): bool
    {
        return $this->viewed;
    }

    public function setViewed(bool $viewed): static
    {
        $this->viewed = $viewed;
        return $this;
    }

    public function isLiked(): bool
    {
        return $this->liked;
    }

    public function setLiked(bool $liked): static
    {
        $this->liked = $liked;
        return $this;
    }

    public function isDismissed(): bool
    {
        return $this->dismissed;
    }

    public function setDismissed(bool $dismissed): static
    {
        $this->dismissed = $dismissed;
        return $this;
    }
}