<?php

namespace App\Repository;

use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Playlist>
 */
class PlaylistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Playlist::class);
    }

    public function findPublicPlaylists(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchPlaylists(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :query OR p.description LIKE :query')
            ->andWhere('p.isPublic = :isPublic')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('isPublic', true)
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPopularPlaylists(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.tracks', 't')
            ->where('p.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->groupBy('p.id')
            ->orderBy('COUNT(t.id)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}