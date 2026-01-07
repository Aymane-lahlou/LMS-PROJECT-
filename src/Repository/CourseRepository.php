<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function findForTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findForStudent(string $specialty, int $year): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.specialty = :specialty')
            ->andWhere('c.targetYear = :year')
            ->setParameter('specialty', $specialty)
            ->setParameter('year', $year)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchCourses(?string $keyword, ?string $specialty, ?int $year): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($keyword) {
            $qb->andWhere('c.title LIKE :kw OR c.description LIKE :kw')
                ->setParameter('kw', '%'.$keyword.'%');
        }

        if ($specialty) {
            $qb->andWhere('c.specialty = :specialty')
                ->setParameter('specialty', $specialty);
        }

        if ($year) {
            $qb->andWhere('c.targetYear = :year')
                ->setParameter('year', (string) $year);
        }

        return $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countForTeacher(User $teacher): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countWithEnrollmentsForTeacher(User $teacher): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('c.enrollments', 'e')
            ->where('c.teacher = :teacher')
            ->andWhere('e.student IS NOT NULL')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countWithoutEnrollmentsForTeacher(User $teacher): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.enrollments', 'e')
            ->where('c.teacher = :teacher')
            ->andWhere('e.student IS NULL')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<array{specialty:string, total:int}>
     */
    public function countBySpecialty(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.specialty AS specialty, COUNT(c.id) AS total')
            ->groupBy('c.specialty')
            ->getQuery()
            ->getResult();
    }
}
