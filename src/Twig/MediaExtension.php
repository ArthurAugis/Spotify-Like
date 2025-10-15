<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * MediaExtension - Twig extension for secure media URLs
 * 
 * Provides helper functions to generate secure media URLs in templates
 */
class MediaExtension extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('media_track', [$this, 'getTrackUrl']),
            new TwigFunction('media_cover', [$this, 'getCoverUrl']),
            new TwigFunction('media_profile', [$this, 'getProfileUrl']),
        ];
    }

    /**
     * Generate secure URL for audio track
     */
    public function getTrackUrl(string $filename): string
    {
        return $this->urlGenerator->generate('app_media_track', [
            'filename' => $filename
        ]);
    }

    /**
     * Generate secure URL for cover image
     */
    public function getCoverUrl(string $filename): string
    {
        return $this->urlGenerator->generate('app_media_cover', [
            'filename' => $filename
        ]);
    }

    /**
     * Generate secure URL for profile picture
     */
    public function getProfileUrl(string $filename): string
    {
        return $this->urlGenerator->generate('app_media_profile', [
            'filename' => $filename
        ]);
    }
}
