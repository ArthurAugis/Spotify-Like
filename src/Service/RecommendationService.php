<?php

namespace App\Service;

use App\Entity\Recommendation;
use App\Entity\Track;
use App\Entity\User;
use App\Repository\TrackRepository;
use App\Repository\RecommendationRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecommendationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TrackRepository $trackRepository,
        private RecommendationRepository $recommendationRepository
    ) {
    }

    /**
     * Generate personalized recommendations using multiple algorithms
     */
    public function generateRecommendationsForUser(User $user, int $maxRecommendations = 10): array
    {
        $recommendations = [];

        $genreRecommendations = $this->getGenreBasedRecommendations($user, 4);
        $recommendations = array_merge($recommendations, $genreRecommendations);

        $artistRecommendations = $this->getArtistBasedRecommendations($user, 3);
        $recommendations = array_merge($recommendations, $artistRecommendations);

        $popularRecommendations = $this->getPopularRecommendations($user, 3);
        $recommendations = array_merge($recommendations, $popularRecommendations);

        // Remove duplicates and limit results
        $uniqueRecommendations = $this->removeDuplicates($recommendations);
        return array_slice($uniqueRecommendations, 0, $maxRecommendations);
    }

    /**
     * Generate recommendations based on user's preferred genres
     */
    private function getGenreBasedRecommendations(User $user, int $limit): array
    {
        $userTracks = $this->trackRepository->findBy(['uploadedBy' => $user]);
        $genres = [];
        
        foreach ($userTracks as $track) {
            if ($track->getGenre()) {
                $genres[] = $track->getGenre();
            }
        }

        if (empty($genres)) {
            return [];
        }

        $favoriteGenres = array_unique($genres);
        $recommendations = [];

        foreach ($favoriteGenres as $genre) {
            $tracks = $this->trackRepository->findByGenreExcludingUser($genre, $user, $limit);
            
            foreach ($tracks as $track) {
                if (!$this->isAlreadyRecommended($user, $track)) {
                    $recommendation = $this->createRecommendation(
                        $user,
                        $track,
                        'genre',
                        $this->calculateGenreScore($track, $favoriteGenres)
                    );
                    $recommendations[] = $recommendation;
                }
            }
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Generate recommendations based on user's preferred artists
     */
    private function getArtistBasedRecommendations(User $user, int $limit): array
    {
        $userTracks = $this->trackRepository->findBy(['uploadedBy' => $user]);
        $artists = [];
        
        foreach ($userTracks as $track) {
            if ($track->getArtist()) {
                $artists[] = $track->getArtist();
            }
        }

        if (empty($artists)) {
            return [];
        }

        $favoriteArtists = array_unique($artists);
        $recommendations = [];

        foreach ($favoriteArtists as $artist) {
            $tracks = $this->trackRepository->findByArtistExcludingUser($artist, $user, $limit);
            
            foreach ($tracks as $track) {
                if (!$this->isAlreadyRecommended($user, $track)) {
                    $recommendation = $this->createRecommendation(
                        $user,
                        $track,
                        'artist',
                        0.8
                    );
                    $recommendations[] = $recommendation;
                }
            }
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Generate trending track recommendations
     */
    private function getPopularRecommendations(User $user, int $limit): array
    {
        $recentTracks = $this->trackRepository->findRecentTracksExcludingUser($user, 30, $limit * 2);
        $recommendations = [];

        foreach ($recentTracks as $track) {
            if (!$this->isAlreadyRecommended($user, $track)) {
                $recommendation = $this->createRecommendation(
                    $user,
                    $track,
                    'trending',
                    $this->calculateTrendingScore($track)
                );
                $recommendations[] = $recommendation;
            }
        }

        return array_slice($recommendations, 0, $limit);
    }

    public function saveRecommendations(array $recommendations): void
    {
        foreach ($recommendations as $recommendation) {
            $this->entityManager->persist($recommendation);
        }
        $this->entityManager->flush();
    }

    private function createRecommendation(User $user, Track $track, string $reason, float $score): Recommendation
    {
        $recommendation = new Recommendation();
        $recommendation->setUser($user)
                      ->setRecommendedTrack($track)
                      ->setReason($reason)
                      ->setScore((string) round($score, 2));

        return $recommendation;
    }

    private function isAlreadyRecommended(User $user, Track $track): bool
    {
        return $this->recommendationRepository->hasRecentRecommendation(
            $user,
            $track->getId(),
            7
        );
    }

    /**
     * Remove duplicate recommendations
     */
    private function removeDuplicates(array $recommendations): array
    {
        $seen = [];
        $unique = [];

        foreach ($recommendations as $recommendation) {
            $trackId = $recommendation->getRecommendedTrack()->getId();
            if (!isset($seen[$trackId])) {
                $seen[$trackId] = true;
                $unique[] = $recommendation;
            }
        }

        return $unique;
    }

    /**
     * Calculate score for genre-based recommendations
     */
    private function calculateGenreScore(Track $track, array $userGenres): float
    {
        $baseScore = 0.6;
        
        // Boost score if track genre matches user's favorite genres
        if (in_array($track->getGenre(), $userGenres)) {
            $baseScore += 0.3;
        }

        // Boost score for newer tracks
        $daysSinceUpload = (new \DateTime())->diff($track->getCreatedAt())->days;
        if ($daysSinceUpload < 7) {
            $baseScore += 0.1;
        }

        return min($baseScore, 1.0);
    }

    /**
     * Calculate score for trending recommendations
     */
    private function calculateTrendingScore(Track $track): float
    {
        $baseScore = 0.5;
        
        // Higher score for newer tracks
        $daysSinceUpload = (new \DateTime())->diff($track->getCreatedAt())->days;
        $recencyBoost = max(0, (30 - $daysSinceUpload) / 30 * 0.4);
        
        return min($baseScore + $recencyBoost, 1.0);
    }

    /**
     * Mark recommendation as liked
     */
    public function markAsLiked(int $recommendationId, User $user): bool
    {
        $recommendation = $this->recommendationRepository->findOneBy([
            'id' => $recommendationId,
            'user' => $user
        ]);

        if ($recommendation) {
            $recommendation->setLiked(true)->setViewed(true);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    /**
     * Mark recommendation as dismissed
     */
    public function markAsDismissed(int $recommendationId, User $user): bool
    {
        $recommendation = $this->recommendationRepository->findOneBy([
            'id' => $recommendationId,
            'user' => $user
        ]);

        if ($recommendation) {
            $recommendation->setDismissed(true)->setViewed(true);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    /**
     * Get formatted recommendations for display
     */
    public function getFormattedRecommendations(User $user, int $limit = 10): array
    {
        $recommendations = $this->recommendationRepository->findActiveRecommendationsForUser($user, $limit);
        $formatted = [];

        foreach ($recommendations as $recommendation) {
            $track = $recommendation->getRecommendedTrack();
            $formatted[] = [
                'id' => $recommendation->getId(),
                'track' => [
                    'id' => $track->getId(),
                    'title' => $track->getTitle(),
                    'artist' => $track->getArtist(),
                    'album' => $track->getAlbum(),
                    'genre' => $track->getGenre(),
                    'audioFile' => $track->getAudioFile(),
                    'coverImage' => $track->getCoverImage(),
                    'duration' => $track->getDuration()
                ],
                'reason' => $this->getReasonText($recommendation->getReason()),
                'score' => $recommendation->getScore(),
                'createdAt' => $recommendation->getCreatedAt()
            ];
        }

        return $formatted;
    }

    /**
     * Get human-readable reason text
     */
    private function getReasonText(string $reason): string
    {
        return match($reason) {
            'genre' => 'Based on your favorite genres',
            'artist' => 'From artists you like',
            'trending' => 'Trending now',
            default => 'Recommended for you'
        };
    }
}