<?php

namespace App\Repository;

use App\Entity\Track;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Track>
 */
class TrackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Track::class);
    }

    public function findTopTracks(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.playCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentTracks(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchTracks(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.title LIKE :query OR t.artist LIKE :query OR t.album LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.playCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByGenre(string $genre, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.genre = :genre')
            ->setParameter('genre', $genre)
            ->orderBy('t.playCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByArtist(string $artist, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.artist LIKE :artist')
            ->setParameter('artist', '%' . $artist . '%')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tracks by genre excluding user's own tracks
     */
    public function findByGenreExcludingUser(string $genre, $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.genre = :genre')
            ->andWhere('t.uploadedBy != :user')
            ->setParameter('genre', $genre)
            ->setParameter('user', $user)
            ->orderBy('t.playCount', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tracks by artist excluding user's own tracks
     */
    public function findByArtistExcludingUser(string $artist, $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.artist = :artist')
            ->andWhere('t.uploadedBy != :user')
            ->setParameter('artist', $artist)
            ->setParameter('user', $user)
            ->orderBy('t.playCount', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent tracks excluding user's own tracks
     */
    public function findRecentTracksExcludingUser($user, int $days = 30, int $limit = 10): array
    {
        $date = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('t')
            ->where('t.createdAt > :date')
            ->andWhere('t.uploadedBy != :user')
            ->setParameter('date', $date)
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}