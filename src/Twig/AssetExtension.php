<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('buildAssetUrl', [$this, 'buildAssetUrl']),
            new TwigFunction('getAssetBasePath', [$this, 'getAssetBasePath']),
            new TwigFunction('formatDuration', [$this, 'formatDuration']),
        ];
    }

    public function buildAssetUrl(string $relativePath = ''): string
    {
        $basePath = $this->getAssetBasePath();

        if (!$relativePath) {
            return $basePath ?: '/';
        }

        $normalizedPath = str_starts_with($relativePath, '/') ? $relativePath : '/' . $relativePath;

        if (!$basePath) {
            return $normalizedPath;
        }

        return $basePath . $normalizedPath;
    }

    public function getAssetBasePath(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return '';
        }

        $basePath = $request->getBasePath();
        
        return $basePath ? rtrim($basePath, '/') : '';
    }

    public function formatDuration(?int $seconds): string
    {
        if (!$seconds || $seconds < 0) {
            return '0:00';
        }
        
        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}