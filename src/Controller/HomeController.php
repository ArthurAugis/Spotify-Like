<?php

namespace App\Controller;

use App\Entity\Track;
use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * HomeController - Main controller for the Onzeer streaming platform homepage
 * 
 * This controller handles the main streaming interface that displays:
 * - Recent playlists
 * - Top/trending tracks based on play count
 * - New releases (recently uploaded tracks)
 */
class HomeController extends AbstractController
{
    /**
     * Main homepage route - displays the streaming interface with real data from database
     * 
     * @param EntityManagerInterface $entityManager Doctrine entity manager for database queries
     * @return Response The rendered homepage template
     */
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Get current user
        $currentUser = $this->getUser();
        
        // Fetch recent playlists: public playlists + user's private playlists if logged in
        $queryBuilder = $entityManager->getRepository(Playlist::class)
            ->createQueryBuilder('p')
            ->where('p.isPublic = :isPublic');
        
        // If user is logged in, also include their private playlists
        if ($currentUser) {
            $queryBuilder
                ->orWhere('p.owner = :currentUser AND p.isPublic = :isPrivate')
                ->setParameter('currentUser', $currentUser)
                ->setParameter('isPrivate', false);
        }
        
        $recentPlaylists = $queryBuilder
            ->setParameter('isPublic', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        // Fetch top tracks based on play count and recency (max 5)
        $topTracks = $entityManager->getRepository(Track::class)
            ->createQueryBuilder('t')
            ->orderBy('t.playCount', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Fetch newest tracks/releases (max 3, ordered by upload date)
        $newReleases = $entityManager->getRepository(Track::class)
            ->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'playlists' => $recentPlaylists,
            'recentTracks' => $newReleases,
            'topTracks' => $topTracks
        ]);
    }

    /**
     * View playlist details (JSON response for AJAX)
     */
    #[Route('/playlist/{id}/view', name: 'app_home_view_playlist', methods: ['GET'])]
    public function viewPlaylist(Playlist $playlist): JsonResponse
    {
        // Check if playlist is public or user owns it
        $currentUser = $this->getUser();
        
        if (!$playlist->isPublic() && ($playlist->getOwner() !== $currentUser)) {
            return new JsonResponse(['error' => 'You can only view public playlists or your own playlists'], 403);
        }

        try {
            $tracks = [];
            $totalDuration = 0;
            
            foreach ($playlist->getTracks() as $track) {
                $tracks[] = [
                    'id' => $track->getId(),
                    'title' => $track->getTitle(),
                    'artist' => $track->getArtist(),
                    'album' => $track->getAlbum(),
                    'genre' => $track->getGenre(),
                    'duration' => $track->getFormattedDuration(),
                    'coverImage' => $track->getCoverImage(),
                    'audioFile' => $track->getAudioFile(),
                    'createdAt' => $track->getCreatedAt()->format('M d, Y')
                ];
                
                if ($track->getDuration()) {
                    $totalDuration += $track->getDuration();
                }
            }

            return new JsonResponse([
                'success' => true,
                'playlist' => [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                    'description' => $playlist->getDescription(),
                    'isPublic' => $playlist->isPublic(),
                    'tracksCount' => count($tracks),
                    'totalDuration' => $this->formatDuration($totalDuration),
                    'createdAt' => $playlist->getCreatedAt()->format('M d, Y'),
                    'owner' => $playlist->getOwner() ? $playlist->getOwner()->getFullName() : 'Unknown',
                    'coverImage' => $playlist->getCoverImage(),
                    'tracks' => $tracks
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred while loading the playlist'], 500);
        }
    }

    /**
     * Format duration in MM:SS format
     */
    private function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}