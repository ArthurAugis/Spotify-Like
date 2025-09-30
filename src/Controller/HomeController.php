<?php

namespace App\Controller;

use App\Entity\Track;
use App\Entity\Playlist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
        // Fetch recent playlists from database (max 4, ordered by creation date)
        $recentPlaylists = $entityManager->getRepository(Playlist::class)
            ->createQueryBuilder('p')
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
            'recentPlaylists' => $recentPlaylists,
            'topTracks' => $topTracks,
            'newReleases' => $newReleases
        ]);
    }
}