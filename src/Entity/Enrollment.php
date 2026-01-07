<?php

namespace App\Entity;

use App\Repository\EnrollmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment', indexes: [
    new ORM\Index(name: 'IDX_ENROLL_STUDENT', columns: ['student_id']),
    new ORM\Index(name: 'IDX_ENROLL_COURSE', columns: ['course_id']),
])]
class Enrollment
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(name: 'student_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $student = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(name: 'course_id', nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    #[ORM\Column(name: 'enrolled_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $enrolledAt;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getEnrolledAt(): \DateTimeInterface
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(\DateTimeInterface $enrolledAt): static
    {
        $this->enrolledAt = $enrolledAt;

        return $this;
    }
}
