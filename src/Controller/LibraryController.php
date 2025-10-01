<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Track;
use App\Entity\Playlist;
use App\Service\LibraryService;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LibraryController - Personal music library management
 * 
 * This controller provides comprehensive CRUD operations for:
 * - User's personal track collection
 * - Playlist creation, editing, and management
 * - Track-to-playlist associations
 * - Library search and filtering capabilities
 * 
 * All methods require user authentication and operate only on
 * the authenticated user's content for security.
 */
 * Handles user's uploaded tracks and created playlists management:
 * - Display user's tracks and playlists
 * - Edit track information
 * - Delete tracks
 * - Create/edit/delete playlists
 * - Add/remove tracks from playlists
 */
#[Route('/library')]
#[IsGranted('ROLE_USER')]
class LibraryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LibraryService $libraryService,
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Display user's library with tracks and playlists
     */
    #[Route('', name: 'app_library')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get user's uploaded tracks with their actual playlist relationships
        $tracks = $this->entityManager->getRepository(Track::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.playlists', 'p')
            ->where('t.uploadedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Get user's playlists
        $playlists = $this->entityManager->getRepository(Playlist::class)
            ->findBy(['owner' => $user], ['createdAt' => 'DESC']);

        // Get library statistics
        $stats = $this->libraryService->getLibraryStats($user);

        return $this->render('library/index.html.twig', [
            'tracks' => $tracks,
            'playlists' => $playlists,
            'stats' => $stats,
        ]);
    }

    /**
     * Delete a track uploaded by the current user
     */
    #[Route('/track/{id}/delete', name: 'app_library_delete_track', methods: ['POST'])]
    public function deleteTrack(Track $track): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this track
        if ($track->getUploadedBy() !== $user) {
            return new JsonResponse(['error' => 'You can only delete your own tracks'], 403);
        }

        try {
            // Use LibraryService to clean up files
            $this->libraryService->cleanupTrackFiles($track);

            $this->entityManager->remove($track);
            $this->entityManager->flush();

            $this->addFlash('success', 'Track deleted successfully!');
            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error deleting track: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edit a track uploaded by the current user
     */
    #[Route('/track/{id}/edit', name: 'app_library_edit_track', methods: ['POST'])]
    public function editTrack(Track $track, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this track
        if ($track->getUploadedBy() !== $user) {
            return new JsonResponse(['error' => 'You can only edit your own tracks'], 403);
        }

        $title = $request->request->get('title');
        $artist = $request->request->get('artist');
        $album = $request->request->get('album');
        $genre = $request->request->get('genre');
        $description = $request->request->get('description');

        if (empty($title) || empty($artist)) {
            return new JsonResponse(['error' => 'Title and artist are required'], 400);
        }

        try {
            // Update basic track information
            $track->setTitle($title);
            $track->setArtist($artist);
            $track->setGenre($genre);
            $track->setDescription($description);

            // Handle playlist assignment
            if (!empty($album)) {
                // Find the playlist by name and owner
                $playlist = $this->entityManager->getRepository(Playlist::class)
                    ->findOneBy(['name' => $album, 'owner' => $user]);
                
                if ($playlist) {
                    // Remove track from all user's playlists first
                    $userPlaylists = $this->entityManager->getRepository(Playlist::class)
                        ->findBy(['owner' => $user]);
                    
                    foreach ($userPlaylists as $userPlaylist) {
                        if ($userPlaylist->getTracks()->contains($track)) {
                            $userPlaylist->removeTrack($track);
                        }
                    }
                    
                    // Add track to the selected playlist
                    $playlist->addTrack($track);
                    $track->setAlbum($album); // Keep album field for display
                } else {
                    // Playlist not found, just set album field
                    $track->setAlbum($album);
                }
            } else {
                // No playlist selected, remove from all playlists
                $userPlaylists = $this->entityManager->getRepository(Playlist::class)
                    ->findBy(['owner' => $user]);
                
                foreach ($userPlaylists as $userPlaylist) {
                    if ($userPlaylist->getTracks()->contains($track)) {
                        $userPlaylist->removeTrack($track);
                    }
                }
                $track->setAlbum(null);
            }

            // Handle audio file upload if provided
            $audioFile = $request->files->get('audio_file');
            if ($audioFile) {
                // Delete old audio file
                if ($track->getAudioFile()) {
                    $this->fileUploadService->delete($track->getAudioFile(), 'tracks');
                }
                
                // Upload new audio file
                $audioFileName = $this->fileUploadService->upload($audioFile, 'tracks');
                $track->setAudioFile($audioFileName);
            }

            // Handle cover image upload if provided
            $coverFile = $request->files->get('cover_file');
            if ($coverFile) {
                // Delete old cover image
                if ($track->getCoverImage()) {
                    $this->fileUploadService->delete($track->getCoverImage(), 'covers');
                }
                
                // Upload new cover image
                $coverFileName = $this->fileUploadService->upload($coverFile, 'covers');
                $track->setCoverImage($coverFileName);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Track updated successfully!');
            return new JsonResponse([
                'success' => true,
                'track' => [
                    'id' => $track->getId(),
                    'title' => $track->getTitle(),
                    'artist' => $track->getArtist(),
                    'album' => $track->getAlbum(),
                    'genre' => $track->getGenre(),
                    'description' => $track->getDescription()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error updating track: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a new playlist
     */
    #[Route('/playlist/create', name: 'app_library_create_playlist', methods: ['POST'])]
    public function createPlaylist(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $name = $request->request->get('name');
        $description = $request->request->get('description', '');
        $isPublic = $request->request->get('is_public', false);

        if (empty($name)) {
            return new JsonResponse(['error' => 'Playlist name is required'], 400);
        }

        try {
            $playlist = new Playlist();
            $playlist->setName($name);
            $playlist->setDescription($description);
            $playlist->setOwner($user);
            $playlist->setIsPublic((bool) $isPublic);

            $this->entityManager->persist($playlist);
            $this->entityManager->flush();

            $this->addFlash('success', 'Playlist created successfully!');
            return new JsonResponse([
                'success' => true,
                'playlist' => [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                    'description' => $playlist->getDescription(),
                    'is_public' => $playlist->isPublic()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error creating playlist: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a playlist created by the current user
     */
    #[Route('/playlist/{id}/delete', name: 'app_library_delete_playlist', methods: ['POST'])]
    public function deletePlaylist(Playlist $playlist): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'You can only delete your own playlists'], 403);
        }

        try {
            $this->entityManager->remove($playlist);
            $this->entityManager->flush();

            $this->addFlash('success', 'Playlist deleted successfully!');
            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error deleting playlist: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edit playlist details
     */
    #[Route('/playlist/{id}/edit', name: 'app_library_edit_playlist', methods: ['POST'])]
    public function editPlaylist(Playlist $playlist, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'You can only edit your own playlists'], 403);
        }

        $name = $request->request->get('name');
        $description = $request->request->get('description', '');
        $isPublic = $request->request->get('is_public', false);

        if (empty($name)) {
            return new JsonResponse(['error' => 'Playlist name is required'], 400);
        }

        try {
            $playlist->setName($name);
            $playlist->setDescription($description);
            $playlist->setIsPublic((bool) $isPublic);

            // Handle cover image upload if provided
            $coverFile = $request->files->get('cover_file');
            if ($coverFile) {
                // Delete old cover image
                if ($playlist->getCoverImage()) {
                    $this->fileUploadService->delete($playlist->getCoverImage(), 'covers');
                }
                
                // Upload new cover image
                $coverFileName = $this->fileUploadService->upload($coverFile, 'covers');
                $playlist->setCoverImage($coverFileName);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Playlist updated successfully!');
            return new JsonResponse([
                'success' => true,
                'playlist' => [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                    'description' => $playlist->getDescription(),
                    'is_public' => $playlist->getIsPublic()
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error updating playlist: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle playlist privacy (public/private)
     */
    #[Route('/playlist/{id}/toggle-privacy', name: 'app_library_toggle_playlist_privacy', methods: ['POST'])]
    public function togglePlaylistPrivacy(Playlist $playlist): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'You can only modify your own playlists'], 403);
        }

        try {
            $playlist->setIsPublic(!$playlist->getIsPublic());
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'is_public' => $playlist->getIsPublic()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error updating playlist privacy: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get playlist content (tracks)
     */
    #[Route('/playlist/{id}/view', name: 'app_library_view_playlist', methods: ['GET'])]
    public function viewPlaylist(Playlist $playlist): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user owns this playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'You can only view your own playlists'], 403);
        }

        try {
            $tracks = [];
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
            }

            return new JsonResponse([
                'success' => true,
                'playlist' => [
                    'id' => $playlist->getId(),
                    'name' => $playlist->getName(),
                    'description' => $playlist->getDescription(),
                    'isPublic' => $playlist->isPublic(),
                    'coverImage' => $playlist->getCoverImage(),
                    'tracksCount' => count($tracks),
                    'totalDuration' => $playlist->getFormattedTotalDuration(),
                    'createdAt' => $playlist->getCreatedAt()->format('M d, Y'),
                    'tracks' => $tracks
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error loading playlist: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get user's playlists for adding tracks (AJAX endpoint)
     */
    #[Route('/playlists-for-track/{trackId}', name: 'app_library_playlists_for_track', methods: ['GET'])]
    public function getPlaylistsForTrack(int $trackId): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $track = $this->entityManager->getRepository(Track::class)->find($trackId);
        if (!$track) {
            return new JsonResponse(['error' => 'Track not found'], 404);
        }

        // Get user's playlists
        $playlists = $this->entityManager->getRepository(Playlist::class)
            ->findBy(['owner' => $user], ['name' => 'ASC']);

        $playlistsData = [];
        foreach ($playlists as $playlist) {
            $playlistsData[] = [
                'id' => $playlist->getId(),
                'name' => $playlist->getName(),
                'tracksCount' => $playlist->getTrackCount(),
                'hasTrack' => $playlist->getTracks()->contains($track)
            ];
        }

        return new JsonResponse([
            'success' => true,
            'track' => [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist()
            ],
            'playlists' => $playlistsData
        ]);
    }

    /**
     * Add track to playlist (AJAX endpoint)
     */
    #[Route('/add-track-to-playlist', name: 'app_library_add_track_to_playlist', methods: ['POST'])]
    public function addTrackToPlaylist(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], 401);
            }
            
            // Handle both JSON and FormData
            $trackId = null;
            $playlistId = null;
            
            $contentType = $request->headers->get('content-type', '');
            
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $trackId = $data['trackId'] ?? null;
                $playlistId = $data['playlistId'] ?? null;
            } else {
                $trackId = $request->request->get('trackId');
                $playlistId = $request->request->get('playlistId');
            }

            if (!$trackId || !$playlistId) {
                return new JsonResponse(['error' => 'Track ID and Playlist ID are required'], 400);
            }

        $track = $this->entityManager->getRepository(Track::class)->find($trackId);
        $playlist = $this->entityManager->getRepository(Playlist::class)->find($playlistId);

        if (!$track || !$playlist) {
            return new JsonResponse(['error' => 'Track or playlist not found'], 404);
        }

        // Check if user owns the playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Check if track is already in playlist
        if ($playlist->getTracks()->contains($track)) {
            return new JsonResponse(['error' => 'Track already in playlist'], 400);
        }

        try {
            $playlist->addTrack($track);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => "Track '{$track->getTitle()}' added to playlist '{$playlist->getName()}'"
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error adding track to playlist: ' . $e->getMessage()], 500);
        }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove track from playlist (AJAX endpoint)
     */
    #[Route('/remove-track-from-playlist', name: 'app_library_remove_track_from_playlist', methods: ['POST'])]
    public function removeTrackFromPlaylist(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            
            if (!$user) {
                return new JsonResponse(['error' => 'User not authenticated'], 401);
            }
            
            // Handle both JSON and FormData
            $trackId = null;
            $playlistId = null;
            
            $contentType = $request->headers->get('content-type', '');
            
            if (str_contains($contentType, 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $trackId = $data['trackId'] ?? null;
                $playlistId = $data['playlistId'] ?? null;
            } else {
                $trackId = $request->request->get('trackId');
                $playlistId = $request->request->get('playlistId');
            }

            if (!$trackId || !$playlistId) {
                return new JsonResponse(['error' => 'Track ID and Playlist ID are required'], 400);
            }

        $track = $this->entityManager->getRepository(Track::class)->find($trackId);
        $playlist = $this->entityManager->getRepository(Playlist::class)->find($playlistId);

        if (!$track || !$playlist) {
            return new JsonResponse(['error' => 'Track or playlist not found'], 404);
        }

        // Check if user owns the playlist
        if ($playlist->getOwner() !== $user) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        try {
            $playlist->removeTrack($track);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => "Track '{$track->getTitle()}' removed from playlist '{$playlist->getName()}'"
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error removing track from playlist: ' . $e->getMessage()], 500);
        }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search tracks for adding to playlist (AJAX endpoint)
     */
    #[Route('/search-tracks', name: 'app_library_search_tracks', methods: ['GET'])]
    public function searchTracks(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $playlistId = $request->query->get('playlist_id');

        if (!$playlistId) {
            return new JsonResponse(['error' => 'Playlist ID required'], 400);
        }

        $playlist = $this->entityManager->getRepository(Playlist::class)->find($playlistId);
        if (!$playlist || $playlist->getOwner() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Playlist not found or access denied'], 403);
        }

        // Search tracks (all tracks, not just user's tracks for variety)
        $qb = $this->entityManager->getRepository(Track::class)->createQueryBuilder('t');
        
        if ($query) {
            $qb->where('t.title LIKE :query OR t.artist LIKE :query OR t.album LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $tracks = $qb->orderBy('t.title', 'ASC')
                     ->setMaxResults(20)
                     ->getQuery()
                     ->getResult();

        $tracksData = [];
        foreach ($tracks as $track) {
            $tracksData[] = [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'album' => $track->getAlbum(),
                'duration' => $track->getFormattedDuration(),
                'coverImage' => $track->getCoverImage(),
                'uploadedBy' => $track->getUploadedBy()->getFirstName() . ' ' . $track->getUploadedBy()->getLastName(),
                'inPlaylist' => $playlist->getTracks()->contains($track)
            ];
        }

        return new JsonResponse([
            'success' => true,
            'tracks' => $tracksData,
            'total' => count($tracksData)
        ]);
    }
}