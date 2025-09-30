<?php

namespace App\Controller;

use App\Entity\Track;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * UploadController - Handles music track and file uploads for the Onzeer platform
 * 
 * This controller manages:
 * - Track upload form display
 * - File upload processing (audio files and cover images)
 * - Track metadata validation and storage
 * - Profile picture uploads
 */
#[Route('/upload')]
class UploadController extends AbstractController
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Display the track upload form
     * 
     * @return Response The upload form template
     */
    #[Route('/track', name: 'app_upload_track')]
    public function uploadTrack(): Response
    {
        return $this->render('upload/track.html.twig', [
            'title' => '',
            'artist' => '',
            'album' => '',
            'genre' => '',
            'description' => ''
        ]);
    }

    /**
     * Process track upload form submission
     * 
     * Validates uploaded files, processes metadata, and saves track to database.
     * Requires both audio file and cover image to be uploaded.
     * 
     * @param Request $request HTTP request containing form data and files
     * @param ValidatorInterface $validator Symfony validator service
     * @return Response Either success redirect or form with errors
     */
    #[Route('/track/submit', name: 'app_upload_track_submit', methods: ['POST'])]
    public function submitTrack(Request $request, ValidatorInterface $validator): Response
    {
        // Ensure user is authenticated
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Extract form data
        $title = $request->request->get('title');
        $artist = $request->request->get('artist');
        $album = $request->request->get('album');
        $genre = $request->request->get('genre');
        $description = $request->request->get('description');
        
        // Extract uploaded files
        $audioFile = $request->files->get('audio_file');
        $coverFile = $request->files->get('cover_file');

        // Validation array to collect all errors
        $errors = [];
        
        // Validate required fields
        if (!$title) {
            $errors[] = 'Le titre est obligatoire';
        }
        if (!$artist) {
            $errors[] = 'L\'artiste est obligatoire';
        }
        if (!$audioFile) {
            $errors[] = 'Le fichier audio est obligatoire';
        }
        if (!$coverFile) {
            $errors[] = 'L\'image de couverture est obligatoire';
        }

        // Validate audio file format and integrity
        if ($audioFile && $audioFile->isValid() && $audioFile->getSize() > 0) {
            try {
                $audioMimeType = $audioFile->getMimeType();
                if (!in_array($audioMimeType, ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'])) {
                    $errors[] = 'Format audio non supporté. Utilisez MP3, WAV ou OGG.';
                }
            } catch (\Exception $e) {
                $errors[] = 'Impossible de vérifier le fichier audio. Assurez-vous qu\'il s\'agit d\'un fichier valide.';
            }
        }

        // Validate cover image format and integrity
        if ($coverFile && $coverFile->isValid() && $coverFile->getSize() > 0) {
            try {
                $coverMimeType = $coverFile->getMimeType();
                if (!in_array($coverMimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/avif'])) {
                    $errors[] = 'Format image non supporté. Utilisez JPG, PNG, GIF ou AVIF.';
                }
            } catch (\Exception $e) {
                $errors[] = 'Impossible de vérifier le fichier image. Assurez-vous qu\'il s\'agit d\'un fichier valide.';
            }
        }

        // If validation errors exist, return form with errors and preserve input data
        if (!empty($errors)) {
            return $this->render('upload/track.html.twig', [
                'errors' => $errors,
                'title' => $title,
                'artist' => $artist,
                'album' => $album,
                'genre' => $genre,
                'description' => $description
            ]);
        }

        // Process file uploads and create track entity
        try {
            $audioFileName = $this->fileUploadService->upload($audioFile, 'tracks');
            
            $coverFileName = $this->fileUploadService->upload($coverFile, 'covers');

            $track = new Track();
            $track->setTitle($title)
                  ->setArtist($artist)
                  ->setAlbum($album)
                  ->setGenre($genre)
                  ->setDescription($description)
                  ->setAudioFile($audioFileName)
                  ->setCoverImage($coverFileName);

            $audioPath = $this->fileUploadService->getUploadDirectory() . '/tracks/' . $audioFileName;
            $duration = $this->getAudioDuration($audioPath);
            if ($duration) {
                $track->setDuration($duration);
            }

            $this->entityManager->persist($track);
            $this->entityManager->flush();

            $this->addFlash('success', 'Track uploadée avec succès !');
            return $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $errors[] = 'Erreur lors de l\'upload : ' . $e->getMessage();
            return $this->render('upload/track.html.twig', [
                'errors' => $errors,
                'title' => $title,
                'artist' => $artist,
                'album' => $album,
                'genre' => $genre,
                'description' => $description
            ]);
        }
    }

    #[Route('/profile-picture', name: 'app_upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non autorisé'], 401);
        }

        $file = $request->files->get('profile_picture');
        
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier sélectionné'], 400);
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            return $this->json(['error' => 'Format non supporté'], 400);
        }

        try {
            if ($user->getProfilePicture()) {
                $this->fileUploadService->delete($user->getProfilePicture(), 'profiles');
            }

            $fileName = $this->fileUploadService->upload($file, 'profiles');
            
            $user->setProfilePicture($fileName);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'avatar_url' => '/uploads/profiles/' . $fileName
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload'], 500);
        }
    }

    private function getAudioDuration(string $filePath): ?int
    {
        if (!file_exists($filePath)) {
            return null;
        }

        return rand(120, 300);
    }
}