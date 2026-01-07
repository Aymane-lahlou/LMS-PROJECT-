<?php

namespace App\Repository;

use App\Entity\ResourceProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResourceProgress>
 */
class ResourceProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceProgress::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompleted(): int
    {
        return (int) $this->createQueryBuilder('rp')
            ->select('COUNT(rp.id)')
            ->andWhere('rp.isCompleted = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function averageTimeSpent(): float
    {
        $result = $this->createQueryBuilder('rp')
            ->select('AVG(rp.timeSpent) AS avg_time')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
