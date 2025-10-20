<?php
namespace App\Controller;

use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class PlaylistController extends AbstractController
{
    #[Route('/api/playlist/{id}/listen', name: 'api_playlist_listen', methods: ['POST'])]
    public function listen(Playlist $playlist, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $playlist->incrementPlayCount();
        $em->flush();
        return new JsonResponse(['success' => true, 'playCount' => $playlist->getPlayCount()]);
    }
}
