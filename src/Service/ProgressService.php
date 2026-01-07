<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Resource;
use App\Entity\ResourceProgress;
use App\Entity\User;
use App\Repository\AttemptRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\ResourceProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProgressService
{
    public function __construct(
        private readonly ResourceProgressRepository $resourceProgressRepository,
        private readonly AttemptRepository $attemptRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function markResourceCompleted(User $student, Resource $resource): ResourceProgress
    {
        $existing = $this->resourceProgressRepository->findOneBy([
            'student' => $student,
            'resource' => $resource,
        ]);

        if ($existing instanceof ResourceProgress) {
            $existing->setIsCompleted(true);
            if ($existing->getCompletedAt() === null) {
                $existing->setCompletedAt(new \DateTimeImmutable());
            }
            $this->entityManager->flush();

            return $existing;
        }

        $progress = new ResourceProgress();
        $progress
            ->setStudent($student)
            ->setResource($resource)
            ->setIsCompleted(true)
            ->setTimeSpent(0)
            ->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        return $progress;
    }

    public function addTimeSpent(User $student, Resource $resource, int $seconds): void
    {
        $progress = $this->resourceProgressRepository->findOneBy([
            'student' => $student,
            'resource' => $resource,
        ]);

        if (!$progress) {
            $progress = new ResourceProgress();
            $progress
                ->setStudent($student)
                ->setResource($resource)
                ->setIsCompleted(false)
                ->setTimeSpent(0);
            $this->entityManager->persist($progress);
        }

        $progress->setTimeSpent($progress->getTimeSpent() + $seconds);
        $this->entityManager->flush();
    }

    public function isResourceCompleted(User $student, Resource $resource): bool
    {
        $progress = $this->resourceProgressRepository->findOneBy([
            'student' => $student,
            'resource' => $resource,
        ]);

        return $progress?->isCompleted() ?? false;
    }

    public function areLessonResourcesCompleted(Lesson $lesson, User $student): bool
    {
        foreach ($lesson->getResources() as $resource) {
            if (!$this->isResourceCompleted($student, $resource)) {
                return false;
            }
        }

        return true;
    }

    public function isLessonCompleted(Lesson $lesson, User $student): bool
    {
        foreach ($lesson->getResources() as $resource) {
            if (!$this->isResourceCompleted($student, $resource)) {
                return false;
            }
        }

        foreach ($lesson->getQuizzes() as $quiz) {
            $passedAttempt = $this->attemptRepository->findOneBy([
                'quiz' => $quiz,
                'student' => $student,
                'passed' => true,
            ]);

            if ($passedAttempt === null) {
                return false;
            }
        }

        return true;
    }

    public function isCourseCompleted(Course $course, User $student): bool
    {
        foreach ($course->getLessons() as $lesson) {
            if (!$this->isLessonCompleted($lesson, $student)) {
                return false;
            }
        }

        return true;
    }

    public function getCourseProgressPercentage(Course $course, User $student): float
    {
        $lessons = $course->getLessons();
        $totalUnits = 0;
        $completedUnits = 0;

        foreach ($lessons as $lesson) {
            $lessonTotals = $this->calculateLessonTotals($lesson, $student);
            $totalUnits += $lessonTotals['total'];
            $completedUnits += $lessonTotals['completed'];
        }

        if ($totalUnits === 0) {
            return 0.0;
        }

        return round(($completedUnits / $totalUnits) * 100, 2);
    }

    public function getAverageCourseProgress(Course $course): float
    {
        $students = $this->enrollmentRepository->findStudentsForCourse($course);
        $count = count($students);
        if ($count === 0) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($students as $student) {
            $sum += $this->getCourseProgressPercentage($course, $student);
        }

        return round($sum / $count, 2);
    }

    /**
     * @return array{total:int,completed:int}
     */
    private function calculateLessonTotals(Lesson $lesson, User $student): array
    {
        $resources = $lesson->getResources();
        $quizzes = $lesson->getQuizzes();

        $totalUnits = $resources->count() + $quizzes->count();
        $completedUnits = 0;

        foreach ($resources as $resource) {
            if ($this->isResourceCompleted($student, $resource)) {
                $completedUnits++;
            }
        }

        foreach ($quizzes as $quiz) {
            $passedAttempt = $this->attemptRepository->findOneBy([
                'quiz' => $quiz,
                'student' => $student,
                'passed' => true,
            ]);

            if ($passedAttempt !== null) {
                $completedUnits++;
            }
        }

        return ['total' => $totalUnits, 'completed' => $completedUnits];
    }

    public function getTotalTimeSpent(Course $course, User $student): int
    {
        $totalSeconds = 0;
        foreach ($course->getLessons() as $lesson) {
            foreach ($lesson->getResources() as $resource) {
                $progress = $this->resourceProgressRepository->findOneBy([
                    'student' => $student,
                    'resource' => $resource,
                ]);
                if ($progress) {
                    $totalSeconds += $progress->getTimeSpent();
                }
            }
        }
        return $totalSeconds;
    }
}
