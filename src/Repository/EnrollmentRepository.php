<?php

namespace App\Repository;

use App\Entity\Enrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function isEnrolled(int $studentId, int $courseId): bool
    {
        return (bool) $this->createQueryBuilder('e')
            ->select('COUNT(e.student)')
            ->andWhere('e.student = :student')
            ->andWhere('e.course = :course')
            ->setParameter('student', $studentId)
            ->setParameter('course', $courseId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return int[]
     */
    public function findCourseIdsForStudent(int $studentId): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.course) AS course_id')
            ->andWhere('e.student = :student')
            ->setParameter('student', $studentId)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn ($row) => (int) $row['course_id'], $rows);
    }

    public function countForCourse(\App\Entity\Course $course): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.student)')
            ->andWhere('e.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function enroll(\App\Entity\User $student, \App\Entity\Course $course): void
    {
        if ($this->isEnrolled($student->getId(), $course->getId())) {
            return;
        }

        $enrollment = new \App\Entity\Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCourse($course);

        $this->getEntityManager()->persist($enrollment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int[] $courseIds
     * @return array<int,int> map courseId => enrollment count
     */
    public function countByCourseIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('e')
            ->select('IDENTITY(e.course) AS course_id, COUNT(e.student) AS total')
            ->andWhere('e.course IN (:courses)')
            ->setParameter('courses', $courseIds)
            ->groupBy('e.course')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['course_id']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return \App\Entity\User[]
     */
    public function findStudentsForCourse(\App\Entity\Course $course): array
    {
        $enrollments = $this->createQueryBuilder('e')
            ->select('e', 's')
            ->join('e.student', 's')
            ->andWhere('e.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getResult();

        $unique = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->getStudent();
            if ($student && !in_array($student, $unique, true)) {
                $unique[] = $student;
            }
        }

        return $unique;
    }
}
