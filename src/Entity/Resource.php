<?php

namespace App\Entity;

use App\Repository\ResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resource', indexes: [new ORM\Index(name: 'IDX_RESOURCE_LESSON', columns: ['lesson_id'])])]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    #[ORM\JoinColumn(name: 'lesson_id', nullable: false, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(name: 'file_path', length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(name: 'file_type', length: 50)]
    private string $fileType = 'pdf';

    #[ORM\Column(name: 'sort_order')]
    private ?int $sortOrder = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    /** @var Collection<int, ResourceProgress> */
    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: ResourceProgress::class, orphanRemoval: true)]
    private Collection $progressRecords;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->progressRecords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): static
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): static
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, ResourceProgress>
     */
    public function getProgressRecords(): Collection
    {
        return $this->progressRecords;
    }

    public function addProgressRecord(ResourceProgress $progress): static
    {
        if (!$this->progressRecords->contains($progress)) {
            $this->progressRecords->add($progress);
            $progress->setResource($this);
        }

        return $this;
    }

    public function removeProgressRecord(ResourceProgress $progress): static
    {
        if ($this->progressRecords->removeElement($progress)) {
            if ($progress->getResource() === $this) {
                $progress->setResource(null);
            }
        }

        return $this;
    }
}
