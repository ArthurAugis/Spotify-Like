<?php

namespace App\Controller;

use App\Entity\Track;
use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SearchController - Global search functionality
 * 
 * Provides advanced search capabilities across all platform content:
 * - Multi-entity search (tracks, playlists, artists)
 * - Auto-complete suggestions for real-time search
 * - Relevance-based result ranking
 * - Public content filtering for discovery
 * 
 * Supports both full-page search results and AJAX endpoints
 * for enhanced user experience.
 */
#[Route('/search')]
class SearchController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Search page
     */
    #[Route('', name: 'app_search')]
    public function index(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if ($query && strlen($query) >= 2) {
            $results = $this->performSearch($query);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results
        ]);
    }

    /**
     * AJAX search endpoint
     */
    #[Route('/ajax', name: 'app_search_ajax', methods: ['GET'])]
    public function ajaxSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (!$query || strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $results = $this->performSearch($query, 5); // Limit for autocomplete

        return new JsonResponse(['results' => $results]);
    }

    /**
     * Perform the actual search
     */
    private function performSearch(string $query, int $limit = 20): array
    {
        $results = [
            'tracks' => [],
            'playlists' => [],
            'artists' => []
        ];

        // Search tracks
        $tracksQuery = $this->entityManager->getRepository(Track::class)
            ->createQueryBuilder('t')
            ->where('t.title LIKE :query OR t.artist LIKE :query OR t.album LIKE :query OR t.genre LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery();

        $tracks = $tracksQuery->getResult();
        foreach ($tracks as $track) {
            $uploadedBy = $track->getUploadedBy();
            $ownerName = $uploadedBy ? 
                $uploadedBy->getFirstName() . ' ' . $uploadedBy->getLastName() : 
                'Unknown User';
                
            $results['tracks'][] = [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'album' => $track->getAlbum(),
                'genre' => $track->getGenre(),
                'coverImage' => $track->getCoverImage(),
                'audioFile' => $track->getAudioFile(),
                'owner' => $ownerName
            ];
        }

        // Search public playlists
        $playlistsQuery = $this->entityManager->getRepository(Playlist::class)
            ->createQueryBuilder('p')
            ->where('p.name LIKE :query OR p.description LIKE :query')
            ->andWhere('p.isPublic = :isPublic')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('isPublic', true)
            ->setMaxResults($limit)
            ->getQuery();

        $playlists = $playlistsQuery->getResult();
        foreach ($playlists as $playlist) {
            $owner = $playlist->getOwner();
            $ownerName = $owner ? 
                $owner->getFirstName() . ' ' . $owner->getLastName() : 
                'Unknown User';
                
            $results['playlists'][] = [
                'id' => $playlist->getId(),
                'name' => $playlist->getName(),
                'description' => $playlist->getDescription(),
                'tracksCount' => $playlist->getTracks()->count(),
                'coverImage' => $playlist->getCoverImage(),
                'owner' => $ownerName
            ];
        }

        // Search artists (unique from tracks)
        $artistsQuery = $this->entityManager->getRepository(Track::class)
            ->createQueryBuilder('t')
            ->select('DISTINCT t.artist')
            ->where('t.artist LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->getQuery();

        $artists = $artistsQuery->getResult();
        foreach ($artists as $artistRow) {
            $artist = $artistRow['artist'];
            
            // Get tracks count for this artist
            $tracksCount = $this->entityManager->getRepository(Track::class)
                ->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.artist = :artist')
                ->setParameter('artist', $artist)
                ->getQuery()
                ->getSingleScalarResult();

            $results['artists'][] = [
                'name' => $artist,
                'tracksCount' => $tracksCount
            ];
        }

        return $results;
    }
}