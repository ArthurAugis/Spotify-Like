<?php

namespace App\Repository;

use App\Entity\Recommendation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recommendation>
 */
class RecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recommendation::class);
    }

    public function findActiveRecommendationsForUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.recommendedTrack', 't')
            ->where('r.user = :user')
            ->andWhere('r.dismissed = false')
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndReason(User $user, string $reason, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.recommendedTrack', 't')
            ->where('r.user = :user')
            ->andWhere('r.reason = :reason')
            ->andWhere('r.dismissed = false')
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setParameter('reason', $reason)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnviewedForUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.viewed = false')
            ->andWhere('r.dismissed = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsViewedForUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.viewed', true)
            ->where('r.user = :user')
            ->andWhere('r.viewed = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function cleanOldRecommendations(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.createdAt < :date')
            ->setParameter('date', $thirtyDaysAgo)
            ->getQuery()
            ->execute();
    }

    public function hasRecentRecommendation(User $user, int $trackId, int $days = 7): bool
    {
        $date = new \DateTime("-{$days} days");
        
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.recommendedTrack = :trackId')
            ->andWhere('r.createdAt > :date')
            ->setParameter('user', $user)
            ->setParameter('trackId', $trackId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}