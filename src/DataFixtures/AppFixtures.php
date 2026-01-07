<?php

namespace App\DataFixtures;

use App\Entity\Attempt;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\Quiz;
use App\Entity\Resource;
use App\Entity\ResourceProgress;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager);

        $courses = $this->createCoursesWithLessonsAndResources($manager, $users['teachers'], $users['students']);

        $this->createEnrollmentsAndProgress($manager, $users['students'], $courses);

        $manager->flush();
    }

    /**
     * @return array{admin: User, teachers: list<User>, students: list<User>}
     */
    private function createUsers(ObjectManager $manager): array
    {
        $admin = (new User())
            ->setEmail('admin@lms.test')
            ->setFirstName('Alice')
            ->setLastName('Admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setStatus(User::STATUS_ACTIVE)
            ->setSpecialty('Administration')
            ->setStudyYear('N/A');
        $admin->setPassword($this->hashPassword('AdminPass123!', $admin));

        $manager->persist($admin);

        $teachers = [];
        $teacherData = [
            ['email' => 'teacher1@lms.test', 'first' => 'Tom', 'last' => 'Teacher', 'specialty' => 'informatique', 'year' => 'Faculty'],
            ['email' => 'teacher2@lms.test', 'first' => 'Tina', 'last' => 'Trainer', 'specialty' => 'genie_logiciel', 'year' => 'Faculty'],
        ];

        foreach ($teacherData as $data) {
            $teacher = (new User())
                ->setEmail($data['email'])
                ->setFirstName($data['first'])
                ->setLastName($data['last'])
                ->setRoles(['ROLE_TEACHER'])
                ->setStatus(User::STATUS_ACTIVE)
                ->setSpecialty($data['specialty'])
                ->setStudyYear($data['year']);
            $teacher->setPassword($this->hashPassword('TeacherPass123!', $teacher));

            $manager->persist($teacher);
            $teachers[] = $teacher;
        }

        $students = [];
        $studentData = [
            ['email' => 'student1@lms.test', 'first' => 'Sam', 'last' => 'Student', 'status' => User::STATUS_ACTIVE, 'specialty' => 'informatique', 'year' => 1],
            ['email' => 'student2@lms.test', 'first' => 'Sara', 'last' => 'Scholar', 'status' => User::STATUS_ACTIVE, 'specialty' => 'genie_logiciel', 'year' => 2],
            ['email' => 'student3@lms.test', 'first' => 'Sean', 'last' => 'Smith', 'status' => User::STATUS_ACTIVE, 'specialty' => 'data', 'year' => 3],
            ['email' => 'student4@lms.test', 'first' => 'Sophie', 'last' => 'Summers', 'status' => User::STATUS_ACTIVE, 'specialty' => 'reseaux', 'year' => 4],
            ['email' => 'student5@lms.test', 'first' => 'Steve', 'last' => 'Stone', 'status' => User::STATUS_ACTIVE, 'specialty' => 'cybersecurite', 'year' => 5],
            ['email' => 'student6@lms.test', 'first' => 'Selena', 'last' => 'Sterling', 'status' => User::STATUS_ACTIVE, 'specialty' => 'informatique', 'year' => 2],
            ['email' => 'pending1@lms.test', 'first' => 'Pat', 'last' => 'Pending', 'status' => User::STATUS_PENDING, 'specialty' => 'genie_logiciel', 'year' => 3],
            ['email' => 'pending2@lms.test', 'first' => 'Paula', 'last' => 'Pending', 'status' => User::STATUS_PENDING, 'specialty' => 'data', 'year' => 1],
        ];

        foreach ($studentData as $data) {
            $student = (new User())
                ->setEmail($data['email'])
                ->setFirstName($data['first'])
                ->setLastName($data['last'])
                ->setRoles(['ROLE_STUDENT'])
                ->setStatus($data['status'])
                ->setSpecialty($data['specialty'])
                ->setStudyYear((string) $data['year']);
            $student->setPassword($this->hashPassword('StudentPass123!', $student));

            $manager->persist($student);
            $students[] = $student;
        }

        return [
            'admin' => $admin,
            'teachers' => $teachers,
            'students' => $students,
        ];
    }

    /**
     * @param list<User> $teachers
     * @param list<User> $students
     * @return list<Course>
     */
    private function createCoursesWithLessonsAndResources(ObjectManager $manager, array $teachers, array $students): array
    {
        $courseSpecs = [
            ['title' => 'Intro to Web Development', 'specialty' => 'informatique', 'year' => 1, 'teacherIndex' => 0],
            ['title' => 'Relational Databases', 'specialty' => 'data', 'year' => 2, 'teacherIndex' => 1],
            ['title' => 'Symfony Foundations', 'specialty' => 'genie_logiciel', 'year' => 2, 'teacherIndex' => 0],
        ];

        $courses = [];
        foreach ($courseSpecs as $spec) {
            $course = (new Course())
                ->setTeacher($teachers[$spec['teacherIndex']])
                ->setTitle($spec['title'])
                ->setDescription($spec['title'].' description')
                    ->setSpecialty($spec['specialty'])
                    ->setTargetYear((string) $spec['year'])
                ->setCoursePicture('default-course.jpg');

            $manager->persist($course);
            $courses[] = $course;

            for ($i = 1; $i <= 3; $i++) {
                $lesson = (new Lesson())
                    ->setCourse($course)
                    ->setTitle(sprintf('%s - Lesson %d', $spec['title'], $i))
                    ->setDescription('Lesson content overview')
                    ->setSortOrder($i);

                $manager->persist($lesson);

                for ($r = 1; $r <= 3; $r++) {
                    $resource = (new Resource())
                        ->setLesson($lesson)
                        ->setTitle(sprintf('Resource %d for %s Lesson %d', $r, $spec['title'], $i))
                        ->setFilePath('/files/sample.pdf')
                        ->setFileType('pdf')
                        ->setSortOrder($r);

                    $manager->persist($resource);
                }

                $quizPath = sprintf('quiz_%s_l%d.json', strtolower(preg_replace('/\\s+/', '_', $spec['title'])), $i);
                $quiz = (new Quiz())
                    ->setLesson($lesson)
                    ->setTitle(sprintf('%s Quiz %d', $spec['title'], $i))
                    ->setJsonPath($quizPath)
                    ->setDescription('Lesson quiz with auto-graded questions');

                $manager->persist($quiz);

                foreach (array_slice($students, 0, 4) as $student) {
                    $attempt = (new Attempt())
                        ->setStudent($student)
                        ->setQuiz($quiz)
                        ->setScore(85)
                        ->setPassed(true);
                    $manager->persist($attempt);
                }
            }
        }

        return $courses;
    }

    /**
     * @param list<User> $students
     * @param list<Course> $courses
     */
    private function createEnrollmentsAndProgress(ObjectManager $manager, array $students, array $courses): void
    {
        foreach ($students as $index => $student) {
            if ($student->getStatus() !== User::STATUS_ACTIVE) {
                continue;
            }

            foreach ($courses as $course) {
                if ($course->getSpecialty() !== $student->getSpecialty() || $course->getTargetYear() !== $student->getStudyYear()) {
                    continue;
                }

                $enrollment = (new Enrollment())
                    ->setStudent($student)
                    ->setCourse($course);
                $manager->persist($enrollment);

                foreach ($course->getLessons() as $lesson) {
                    foreach ($lesson->getResources() as $resource) {
                        $progress = (new ResourceProgress())
                            ->setStudent($student)
                            ->setResource($resource)
                            ->setIsCompleted(true)
                            ->setTimeSpent(120)
                            ->setCompletedAt(new \DateTimeImmutable());
                        $manager->persist($progress);
                    }
                }
            }
        }
    }

    private function hashPassword(string $plain, ?User $user = null): string
    {
        return $this->passwordHasher->hashPassword($user ?? new User(), $plain);
    }
}
