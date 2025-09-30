<?php

namespace App\Service;

use App\Entity\Track;
use App\Entity\Playlist;
use App\Entity\User;

/**
 * Service for managing user's music library
 * 
 * Provides utilities for handling user's tracks and playlists:
 * - File cleanup when deleting tracks
 * - Statistics calculation
 * - Library organization
 */
class LibraryService
{
    public function __construct(
        private string $uploadsDirectory
    ) {}

    /**
     * Calculate library statistics for a user
     */
    public function getLibraryStats(User $user): array
    {
        $tracks = $user->getTracks();
        $playlists = $user->getPlaylists();
        
        $totalTracks = $tracks->count();
        $totalPlaylists = $playlists->count();
        $totalDuration = 0;
        $genres = [];

        foreach ($tracks as $track) {
            $totalDuration += $track->getDuration() ?? 0;
            if ($track->getGenre()) {
                $genres[$track->getGenre()] = ($genres[$track->getGenre()] ?? 0) + 1;
            }
        }

        $publicPlaylists = 0;
        foreach ($playlists as $playlist) {
            if ($playlist->getIsPublic()) {
                $publicPlaylists++;
            }
        }

        return [
            'total_tracks' => $totalTracks,
            'total_playlists' => $totalPlaylists,
            'public_playlists' => $publicPlaylists,
            'private_playlists' => $totalPlaylists - $publicPlaylists,
            'total_duration' => $totalDuration,
            'formatted_duration' => $this->formatDuration($totalDuration),
            'top_genres' => $this->getTopGenres($genres),
            'storage_used' => $this->calculateStorageUsed($tracks)
        ];
    }

    /**
     * Format duration in seconds to human readable format
     */
    public function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . $remainingSeconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get top genres sorted by frequency
     */
    private function getTopGenres(array $genres): array
    {
        if (empty($genres)) {
            return [];
        }
        
        arsort($genres);
        return array_slice($genres, 0, 5, true);
    }

    /**
     * Calculate storage used by user's tracks
     */
    private function calculateStorageUsed($tracks): array
    {
        $totalBytes = 0;
        $trackCount = 0;

        foreach ($tracks as $track) {
            $trackPath = $this->uploadsDirectory . '/tracks/' . $track->getAudioFile();
            if (file_exists($trackPath)) {
                $totalBytes += filesize($trackPath);
            }

            // Add cover image size if exists
            if ($track->getCoverImage()) {
                $coverPath = $this->uploadsDirectory . '/covers/' . $track->getCoverImage();
                if (file_exists($coverPath)) {
                    $totalBytes += filesize($coverPath);
                }
            }
            $trackCount++;
        }

        return [
            'bytes' => $totalBytes,
            'formatted' => $this->formatBytes($totalBytes),
            'average_per_track' => $trackCount > 0 ? $this->formatBytes($totalBytes / $trackCount) : '0 B'
        ];
    }

    /**
     * Format bytes to human readable format
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Clean up files associated with a track
     */
    public function cleanupTrackFiles(Track $track): bool
    {
        $success = true;

        // Remove audio file
        $trackPath = $this->uploadsDirectory . '/tracks/' . $track->getAudioFile();
        if (file_exists($trackPath)) {
            $success = unlink($trackPath) && $success;
        }

        // Remove cover image if exists
        if ($track->getCoverImage()) {
            $coverPath = $this->uploadsDirectory . '/covers/' . $track->getCoverImage();
            if (file_exists($coverPath)) {
                $success = unlink($coverPath) && $success;
            }
        }

        return $success;
    }

    /**
     * Get recently uploaded tracks by user
     */
    public function getRecentTracks(User $user, int $limit = 10): array
    {
        return $user->getTracks()
            ->matching(
                \Doctrine\Common\Collections\Criteria::create()
                    ->orderBy(['createdAt' => 'DESC'])
                    ->setMaxResults($limit)
            )->toArray();
    }

    /**
     * Get recently updated playlists by user
     */
    public function getRecentPlaylists(User $user, int $limit = 10): array
    {
        return $user->getPlaylists()
            ->matching(
                \Doctrine\Common\Collections\Criteria::create()
                    ->orderBy(['updatedAt' => 'DESC'])
                    ->setMaxResults($limit)
            )->toArray();
    }
}