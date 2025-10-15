<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * MediaController - Secure file serving controller
 * 
 * This controller protects uploaded files by:
 * - Requiring authentication to access media files
 * - Preventing direct URL access to uploads
 * - Enabling streaming for audio files
 * - Providing centralized access control
 */
#[Route('/media')]
class MediaController extends AbstractController
{
    public function __construct(
        private string $uploadsDirectory
    ) {
    }

    /**
     * Serve audio track files with streaming support
     * 
     * @param string $filename The audio file to serve
     * @return Response Binary file response with streaming
     */
    #[Route('/track/{filename}', name: 'app_media_track', methods: ['GET'])]
    public function serveTrack(string $filename): Response
    {
        // Require authentication (remember-me users allowed for images)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $filePath = $this->uploadsDirectory . '/tracks/' . $filename;

        // Security: Prevent directory traversal attacks
        $realPath = realpath($filePath);
        $uploadsRealPath = realpath($this->uploadsDirectory . '/tracks');
        
        if (!$realPath || !str_starts_with($realPath, $uploadsRealPath)) {
            throw $this->createNotFoundException('File not found');
        }

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Audio file not found');
        }

        $response = new BinaryFileResponse($filePath);
        
        // Enable streaming for audio files
        $response->headers->set('Content-Type', 'audio/mpeg');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        // Enable caching for better performance
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Serve cover image files
     * 
     * @param string $filename The cover image to serve
     * @return Response Binary file response
     */
    #[Route('/cover/{filename}', name: 'app_media_cover', methods: ['GET'])]
    public function serveCover(string $filename): Response
    {
    // Require authentication (remember-me users allowed for images)
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $filePath = $this->uploadsDirectory . '/covers/' . $filename;

        // Security: Prevent directory traversal attacks
        $realPath = realpath($filePath);
        $uploadsRealPath = realpath($this->uploadsDirectory . '/covers');
        
        if (!$realPath || !str_starts_with($realPath, $uploadsRealPath)) {
            throw $this->createNotFoundException('File not found');
        }

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Cover image not found');
        }

        $response = new BinaryFileResponse($filePath);
        
        // Set appropriate content type for images
        $mimeType = mime_content_type($filePath);
        $response->headers->set('Content-Type', $mimeType);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        // Enable caching for better performance
        $response->setPublic();
        $response->setMaxAge(86400); // 24 hours for images
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Serve profile picture files
     * 
     * @param string $filename The profile picture to serve
     * @return Response Binary file response
     */
    #[Route('/profile/{filename}', name: 'app_media_profile', methods: ['GET'])]
    public function serveProfile(string $filename): Response
    {
        // Allow remember-me users to view profile images so UI avatars load
        // for users who signed in via remember-me cookies.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $filePath = $this->uploadsDirectory . '/profiles/' . $filename;

        // Security: Prevent directory traversal attacks
        $realPath = realpath($filePath);
        $uploadsRealPath = realpath($this->uploadsDirectory . '/profiles');
        
        if (!$realPath || !str_starts_with($realPath, $uploadsRealPath)) {
            throw $this->createNotFoundException('File not found');
        }

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Profile picture not found');
        }

        $response = new BinaryFileResponse($filePath);
        
        // Set appropriate content type for images
        $mimeType = mime_content_type($filePath);
        $response->headers->set('Content-Type', $mimeType);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        // Enable caching for better performance
        $response->setPublic();
        $response->setMaxAge(86400); // 24 hours for images
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }
}
