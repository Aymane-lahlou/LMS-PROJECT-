<?php

namespace App\Entity;

use App\Repository\AttemptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttemptRepository::class)]
#[ORM\Table(name: 'attempt', indexes: [
    new ORM\Index(name: 'IDX_ATTEMPT_STUDENT', columns: ['student_id']),
    new ORM\Index(name: 'IDX_ATTEMPT_QUIZ', columns: ['quiz_id']),
])]
class Attempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'student_id', nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'attempts')]
    #[ORM\JoinColumn(name: 'quiz_id', nullable: false, onDelete: 'CASCADE')]
    private ?Quiz $quiz = null;

    #[ORM\Column]
    private ?int $score = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $passed = false;

    #[ORM\Column(name: 'attempted_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $attemptedAt;

    public function __construct()
    {
        $this->attemptedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): static
    {
        $this->passed = $passed;

        return $this;
    }

    public function getAttemptedAt(): \DateTimeInterface
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(\DateTimeInterface $attemptedAt): static
    {
        $this->attemptedAt = $attemptedAt;

        return $this;
    }
}
