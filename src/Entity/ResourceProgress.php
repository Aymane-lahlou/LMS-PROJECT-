<?php

namespace App\Entity;

use App\Repository\ResourceProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceProgressRepository::class)]
#[ORM\Table(name: 'resource_progress', indexes: [
    new ORM\Index(name: 'IDX_PROG_STUDENT', columns: ['student_id']),
    new ORM\Index(name: 'IDX_PROG_RESOURCE', columns: ['resource_id']),
])]
class ResourceProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resourceProgressRecords')]
    #[ORM\JoinColumn(name: 'student_id', nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(inversedBy: 'progressRecords')]
    #[ORM\JoinColumn(name: 'resource_id', nullable: false, onDelete: 'CASCADE')]
    private ?Resource $resource = null;

    #[ORM\Column(name: 'is_completed', type: Types::BOOLEAN)]
    private bool $isCompleted = false;

    #[ORM\Column(name: 'time_spent')]
    private int $timeSpent = 0;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

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

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getTimeSpent(): int
    {
        return $this->timeSpent;
    }

    public function setTimeSpent(int $timeSpent): static
    {
        $this->timeSpent = $timeSpent;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
