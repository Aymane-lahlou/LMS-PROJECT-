<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_BANNED = 'BANNED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'first_name', length: 100)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 100)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(groups: ['student'])]
    private ?string $specialty = null;

    #[ORM\Column(name: 'study_year', length: 50, nullable: true)]
    #[Assert\NotBlank(groups: ['student'])]
    private ?string $studyYear = null;

    #[ORM\Column(length: 255)]
    private string $avatar = 'default-avatar.png';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    /** @var Collection<int, Course> */
    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Course::class)]
    private Collection $coursesTaught;

    /** @var Collection<int, Enrollment> */
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Enrollment::class, orphanRemoval: true)]
    private Collection $enrollments;

    /** @var Collection<int, ResourceProgress> */
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: ResourceProgress::class, orphanRemoval: true)]
    private Collection $resourceProgressRecords;

    /** @var Collection<int, Attempt> */
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Attempt::class, orphanRemoval: true)]
    private Collection $attempts;

    public function __construct()
    {
        $this->roles = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->coursesTaught = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->resourceProgressRecords = new ArrayCollection();
        $this->attempts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): static
    {
        $this->specialty = $specialty;

        return $this;
    }

    public function getStudyYear(): ?string
    {
        return $this->studyYear;
    }

    public function setStudyYear(?string $studyYear): static
    {
        $this->studyYear = $studyYear;

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): static
    {
        $this->avatar = $avatar;

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
     * @return Collection<int, Course>
     */
    public function getCoursesTaught(): Collection
    {
        return $this->coursesTaught;
    }

    public function addCourseTaught(Course $course): static
    {
        if (!$this->coursesTaught->contains($course)) {
            $this->coursesTaught->add($course);
            $course->setTeacher($this);
        }

        return $this;
    }

    public function removeCourseTaught(Course $course): static
    {
        if ($this->coursesTaught->removeElement($course)) {
            if ($course->getTeacher() === $this) {
                $course->setTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Enrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    public function addEnrollment(Enrollment $enrollment): static
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setStudent($this);
        }

        return $this;
    }

    public function removeEnrollment(Enrollment $enrollment): static
    {
        if ($this->enrollments->removeElement($enrollment)) {
            if ($enrollment->getStudent() === $this) {
                $enrollment->setStudent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ResourceProgress>
     */
    public function getResourceProgressRecords(): Collection
    {
        return $this->resourceProgressRecords;
    }

    public function addResourceProgressRecord(ResourceProgress $resourceProgress): static
    {
        if (!$this->resourceProgressRecords->contains($resourceProgress)) {
            $this->resourceProgressRecords->add($resourceProgress);
            $resourceProgress->setStudent($this);
        }

        return $this;
    }

    public function removeResourceProgressRecord(ResourceProgress $resourceProgress): static
    {
        if ($this->resourceProgressRecords->removeElement($resourceProgress)) {
            if ($resourceProgress->getStudent() === $this) {
                $resourceProgress->setStudent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Attempt>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }

    public function addAttempt(Attempt $attempt): static
    {
        if (!$this->attempts->contains($attempt)) {
            $this->attempts->add($attempt);
            $attempt->setStudent($this);
        }

        return $this;
    }

    public function removeAttempt(Attempt $attempt): static
    {
        if ($this->attempts->removeElement($attempt)) {
            if ($attempt->getStudent() === $this) {
                $attempt->setStudent(null);
            }
        }

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function eraseCredentials(): void
    {
    }
}
