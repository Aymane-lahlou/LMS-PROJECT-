<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Resource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CourseManagementService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createCourse(
        User $teacher,
        string $title,
        ?string $description,
        string $specialty,
        int $targetYear,
        ?string $coursePicture = null
    ): Course {
        $course = new Course();
        $course
            ->setTeacher($teacher)
            ->setTitle($title)
            ->setDescription($description)
            ->setSpecialty($specialty)
            ->setTargetYear((string) $targetYear);

        if ($coursePicture) {
            $course->setCoursePicture($coursePicture);
        }

        $this->entityManager->persist($course);
        $this->entityManager->flush();

        return $course;
    }

    public function updateCourse(
        Course $course,
        string $title,
        ?string $description,
        string $specialty,
        int $targetYear,
        ?string $coursePicture = null
    ): Course {
        $course
            ->setTitle($title)
            ->setDescription($description)
            ->setSpecialty($specialty)
            ->setTargetYear((string) $targetYear);

        if ($coursePicture) {
            $course->setCoursePicture($coursePicture);
        }

        $this->entityManager->flush();

        return $course;
    }

    public function createLesson(
        Course $course,
        string $title,
        ?string $description,
        int $sortOrder
    ): Lesson {
        $lesson = new Lesson();
        $lesson
            ->setCourse($course)
            ->setTitle($title)
            ->setDescription($description)
            ->setSortOrder($sortOrder);

        $this->entityManager->persist($lesson);
        $this->entityManager->flush();

        return $lesson;
    }

    public function createResource(
        Lesson $lesson,
        string $title,
        string $filePath,
        string $fileType,
        int $sortOrder
    ): Resource {
        $resource = new Resource();
        $resource
            ->setLesson($lesson)
            ->setTitle($title)
            ->setFilePath($filePath)
            ->setFileType($fileType)
            ->setSortOrder($sortOrder);

        $this->entityManager->persist($resource);
        $this->entityManager->flush();

        return $resource;
    }

    public function updateResource(
        Resource $resource,
        string $title,
        ?string $filePath,
        int $sortOrder
    ): Resource {
        $resource
            ->setTitle($title)
            ->setSortOrder($sortOrder);

        if ($filePath) {
            $resource->setFilePath($filePath);
        }

        $this->entityManager->flush();

        return $resource;
    }

    public function updateLesson(
        Lesson $lesson,
        string $title,
        ?string $description,
        int $sortOrder
    ): Lesson {
        $lesson
            ->setTitle($title)
            ->setDescription($description)
            ->setSortOrder($sortOrder);

        $this->entityManager->flush();

        return $lesson;
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $this->entityManager->remove($lesson);
        $this->entityManager->flush();
    }
}
