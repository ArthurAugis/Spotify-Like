<?php

namespace App\Controller;

use App\Entity\Track;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class TrackController extends AbstractController
{
    #[Route('/api/track/{id}/listen', name: 'api_track_listen', methods: ['POST'])]
    public function listen(Track $track, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $track->incrementPlayCount();
        $em->flush();
        return new JsonResponse(['success' => true, 'playCount' => $track->getPlayCount()]);
    }
}
