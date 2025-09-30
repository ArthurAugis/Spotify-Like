<?php

namespace App\Service;

use App\Entity\User;

class AvatarService
{
    public function getAvatarUrl(User $user): string
    {
        if ($user->getProfilePicture()) {
            return '/uploads/profiles/' . $user->getProfilePicture();
        }
        
        return $this->generateDefaultAvatar($user);
    }

    public function generateDefaultAvatar(User $user): string
    {
        $initial = $user->getInitial();
        $colors = [
            '#007bff', 
            '#28a745', 
            '#dc3545', 
            '#ffc107', 
            '#17a2b8', 
            '#6f42c1', 
            '#e83e8c', 
            '#fd7e14', 
        ];
        
        $colorIndex = ($user->getId() ?? 0) % count($colors);
        $backgroundColor = $colors[$colorIndex];
        
        return "https://via.placeholder.com/100x100/{$this->hexToColor($backgroundColor)}/ffffff?text={$initial}";
    }

    private function hexToColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return $hex;
    }

    public function getInitialFromName(string $name): string
    {
        return strtoupper(substr(trim($name), 0, 1)) ?: 'U';
    }
}