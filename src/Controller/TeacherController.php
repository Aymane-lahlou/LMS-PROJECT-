<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Quiz;
use App\Entity\Resource as LessonResource;
use App\Repository\CourseRepository;
use App\Repository\EnrollmentRepository;
use App\Service\CourseManagementService;
use App\Service\DomainChoiceProvider;
use App\Service\FileStorageService;
use App\Service\QuizService;
use App\Form\ResourceUploadType;
use App\Form\QuizJsonUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class TeacherController extends AbstractController
{
    #[Route('/teacher/dashboard', name: 'teacher_dashboard')]
    public function dashboard(
        Request $request,
        CourseRepository $courseRepository,
        CourseManagementService $courseService,
        DomainChoiceProvider $choiceProvider,
        EnrollmentRepository $enrollmentRepository,
        \App\Service\ProgressService $progressService,
        FileStorageService $fileStorageService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');

        $user = $this->getUser();
        $courses = $courseRepository->findForTeacher($user);

        $lessonCount = 0;
        $resourceCount = 0;
        $quizCount = 0;
        $enrollmentCounts = [];
        $averageCourseProgress = [];
        foreach ($courses as $course) {
            $lessonCount += $course->getLessons()->count();
            foreach ($course->getLessons() as $lesson) {
                $resourceCount += $lesson->getResources()->count();
                $quizCount += $lesson->getQuizzes()->count();
            }
            $enrollmentCounts[$course->getId()] = $enrollmentRepository->countForCourse($course);
            $averageCourseProgress[$course->getId()] = $progressService->getAverageCourseProgress($course);
        }
        $withEnrollments = $courseRepository->countWithEnrollmentsForTeacher($user);
        $withoutEnrollments = $courseRepository->countWithoutEnrollmentsForTeacher($user);

        $formErrors = [];

        if ($request->isMethod('POST') && $this->isCsrfTokenValid('teacher_create_course', (string) $request->request->get('_token'))) {
            $specialty = (string) $request->request->get('specialty');
            $targetYear = (int) $request->request->get('target_year');
            $allowedSpecialties = $choiceProvider->getSpecialtyChoices();
            $allowedYears = $choiceProvider->getStudyYearChoices();

            if (!in_array($specialty, $allowedSpecialties, true) || !in_array($targetYear, $allowedYears, true)) {
                $formErrors[] = 'Veuillez sélectionner une spécialité et une année valides.';
            } else {
                 // Handle File Upload
                $picturePath = null;
                
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
                $file = $request->files->get('course_picture_file');
                if ($file) {
                    $picturePath = $fileStorageService->storeCoursePicture($file);
                } elseif ($request->request->get('course_picture')) {
                    $picturePath = (string) $request->request->get('course_picture');
                }

                $courseService->createCourse(
                    $user,
                    (string) $request->request->get('title'),
                    (string) $request->request->get('description'),
                    $specialty,
                    $targetYear,
                    $picturePath
                );

                $this->addFlash('success', 'Course created.');

                return $this->redirectToRoute('teacher_dashboard');
            }
        }

        return $this->render('teacher/dashboard.html.twig', [
            'courses' => $courses,
            'stats' => [
                'courses' => count($courses),
                'lessons' => $lessonCount,
                'resources' => $resourceCount,
                'quizzes' => $quizCount,
                'courses_with_enrollments' => $withEnrollments,
                'courses_without_enrollments' => $withoutEnrollments,
            ],
            'enrollmentCounts' => $enrollmentCounts,
            'averageCourseProgress' => $averageCourseProgress,
            'specialties' => $choiceProvider->getSpecialtyChoices(),
            'studyYears' => $choiceProvider->getStudyYearChoices(),
            'formErrors' => $formErrors,
        ]);
    }

    #[Route('/teacher/course/{id}/edit', name: 'teacher_edit_course', methods: ['GET', 'POST'])]
    public function editCourse(
        Request $request,
        Course $course,
        CourseManagementService $courseService,
        DomainChoiceProvider $choiceProvider,
        FileStorageService $fileStorageService
    ): Response {
        $this->ensureTeacherOwnsCourse($course);

        $formErrors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('teacher_edit_course_'.$course->getId(), (string) $request->request->get('_token'))) {
                $formErrors[] = 'Jeton CSRF invalide.';
            } else {
                $specialty = (string) $request->request->get('specialty');
                $targetYear = (int) $request->request->get('target_year');
                $allowedSpecialties = $choiceProvider->getSpecialtyChoices();
                $allowedYears = $choiceProvider->getStudyYearChoices();
                
                // Handle File Upload
                $picturePath = null;
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
                $file = $request->files->get('course_picture_file');
                if ($file) {
                    $picturePath = $fileStorageService->storeCoursePicture($file);
                } elseif ($request->request->get('course_picture_url')) {
                    $picturePath = (string) $request->request->get('course_picture_url');
                }

                if (!in_array($specialty, $allowedSpecialties, true) || !in_array($targetYear, $allowedYears, true)) {
                    $formErrors[] = 'Veuillez sélectionner une spécialité et une année valides.';
                } else {
                    $courseService->updateCourse(
                        $course,
                        (string) $request->request->get('title'),
                        (string) $request->request->get('description'),
                        $specialty,
                        $targetYear,
                        $picturePath
                    );

                    $this->addFlash('success', 'Cours mis à jour avec succès.');
                    return $this->redirectToRoute('teacher_dashboard');
                }
            }
        }

        return $this->render('teacher/edit_course.html.twig', [
            'course' => $course,
            'specialties' => $choiceProvider->getSpecialtyChoices(),
            'studyYears' => $choiceProvider->getStudyYearChoices(),
            'formErrors' => $formErrors,
        ]);
    }


    #[Route('/teacher/course/{id}', name: 'teacher_course_details', methods: ['GET'])]
    public function viewCourseDetails(
        Course $course,
        EnrollmentRepository $enrollmentRepository,
        \App\Service\ProgressService $progressService
    ): Response {
        $this->ensureTeacherOwnsCourse($course);

        $students = $enrollmentRepository->findStudentsForCourse($course);
        $studentStats = [];

        foreach ($students as $student) {
            $studentStats[] = [
                'user' => $student,
                'progress' => $progressService->getCourseProgressPercentage($course, $student),
                'timeSpent' => $progressService->getTotalTimeSpent($course, $student),
            ];
        }

        return $this->render('teacher/course_details.html.twig', [
            'course' => $course,
            'studentStats' => $studentStats,
        ]);
    }

    #[Route('/teacher/course/{id}/lesson/new', name: 'teacher_create_lesson', methods: ['POST'])]
    public function createLesson(
        Request $request,
        Course $course,
        CourseManagementService $courseService
    ): RedirectResponse {
        $this->ensureTeacherOwnsCourse($course);

        if ($this->isCsrfTokenValid('teacher_create_lesson_'.$course->getId(), (string) $request->request->get('_token'))) {
            $courseService->createLesson(
                $course,
                (string) $request->request->get('title'),
                (string) $request->request->get('description'),
                (int) $request->request->get('sort_order', 1)
            );

            $this->addFlash('success', 'Leçon créée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('teacher_edit_course', ['id' => $course->getId()]);
    }

    #[Route('/teacher/lesson/{id}/edit', name: 'teacher_edit_lesson', methods: ['GET', 'POST'])]
    public function editLesson(
        Request $request,
        Lesson $lesson,
        CourseManagementService $courseService
    ): Response {
        $this->ensureTeacherOwnsCourse($lesson->getCourse());

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('teacher_edit_lesson_'.$lesson->getId(), (string) $request->request->get('_token'))) {
                $courseService->updateLesson(
                    $lesson,
                    (string) $request->request->get('title'),
                    (string) $request->request->get('description'),
                    (int) $request->request->get('sort_order', 1)
                );
                $this->addFlash('success', 'Leçon modifiée avec succès.');
                return $this->redirectToRoute('teacher_edit_course', ['id' => $lesson->getCourse()->getId()]);
            }
        }

        return $this->render('teacher/edit_lesson.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/teacher/lesson/{id}/delete', name: 'teacher_delete_lesson', methods: ['POST'])]
    public function deleteLesson(
        Request $request,
        Lesson $lesson,
        CourseManagementService $courseService
    ): RedirectResponse {
        $course = $lesson->getCourse();
        $this->ensureTeacherOwnsCourse($course);

        if ($this->isCsrfTokenValid('teacher_delete_lesson_'.$lesson->getId(), (string) $request->request->get('_token'))) {
            $courseService->deleteLesson($lesson);
            $this->addFlash('success', 'Leçon supprimée.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('teacher_edit_course', ['id' => $course->getId()]);
    }


    #[Route('/teacher/lesson/{id}/resource/new', name: 'teacher_create_resource', methods: ['GET', 'POST'])]
    public function createResource(
        Request $request,
        Lesson $lesson,
        CourseManagementService $courseService,
        FileStorageService $fileStorageService
    ): Response {
        $this->ensureTeacherOwnsCourse($lesson->getCourse());

        $form = $this->createForm(ResourceUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form->get('file')->getData();
            $path = $fileStorageService->storeResource($file);

            $courseService->createResource(
                $lesson,
                (string) $form->get('title')->getData(),
                $path,
                'pdf',
                (int) $form->get('sort_order')->getData() ?: 1
            );
            $this->addFlash('success', 'Resource uploaded.');

            return $this->redirectToRoute('teacher_dashboard');
        }

        return $this->render('teacher/resource_upload.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
        ]);
        return $this->render('teacher/resource_upload.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/teacher/resource/{id}/edit', name: 'teacher_edit_resource', methods: ['GET', 'POST'])]
    public function editResource(
        Request $request,
        LessonResource $resource,
        CourseManagementService $courseService,
        FileStorageService $fileStorageService
    ): Response {
        $this->ensureTeacherOwnsCourse($resource->getLesson()?->getCourse());

        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('teacher_edit_resource_'.$resource->getId(), (string) $request->request->get('_token'))) {
                $filePath = null;
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
                $file = $request->files->get('file');
                
                if ($file) {
                    $filePath = $fileStorageService->storeResource($file);
                }

                $courseService->updateResource(
                    $resource,
                    (string) $request->request->get('title'),
                    $filePath,
                    (int) $request->request->get('sort_order', 1)
                );

                $this->addFlash('success', 'Ressource mise à jour.');
                return $this->redirectToRoute('teacher_edit_course', ['id' => $resource->getLesson()->getCourse()->getId()]);
            }
        }

        return $this->render('teacher/edit_resource.html.twig', [
            'resource' => $resource,
        ]);
    }

    #[Route('/teacher/lesson/{id}/quiz/new', name: 'teacher_create_quiz', methods: ['GET', 'POST'])]
    public function createQuiz(
        Request $request,
        Lesson $lesson,
        QuizService $quizService,
        FileStorageService $fileStorageService
    ): Response {
        $this->ensureTeacherOwnsCourse($lesson->getCourse());

        // Old form-based builder removed; rely on JSON upload or dynamic generator below.

        $uploadForm = $this->createForm(QuizJsonUploadType::class);
        $uploadForm->handleRequest($request);

        // Dynamic client-side generator POST handling
        if ($request->isMethod('POST') && $request->request->has('dynamic_title')) {
            $token = (string) $request->request->get('_token_dynamic_quiz');
            if (!$this->isCsrfTokenValid('dynamic_quiz_'.$lesson->getId(), $token)) {
                $this->addFlash('error', 'Jeton CSRF invalide pour le générateur dynamique.');
                return $this->redirectToRoute('teacher_create_quiz', ['id' => $lesson->getId()]);
            }

            $rawJson = (string) $request->request->get('dynamic_quiz_json');
            $decoded = json_decode($rawJson, true);

            // Fallback: build JSON server-side if none provided or invalid
            if (!is_array($decoded) || empty($decoded['questions'])) {
                $count = (int) $request->request->get('dynamic_question_count', 0);
                $questions = [];
                for ($i = 1; $i <= $count; $i++) {
                    $text = (string) $request->request->get("q{$i}_text", '');
                    $choices = [];
                    for ($n = 1; $n <= 4; $n++) {
                        $choices[] = (string) $request->request->get("q{$i}_option_{$n}", '');
                    }
                    $answer = max(0, ((int) $request->request->get("q{$i}_answer", 1)) - 1);
                    if (trim($text) !== '') {
                        $questions[] = [
                            'question' => $text,
                            'choices' => $choices,
                            'answer' => $answer,
                        ];
                    }
                }
                $decoded = ['passing_score' => 70, 'questions' => $questions];
            }

            if (!is_array($decoded) || empty($decoded['questions'])) {
                $this->addFlash('error', 'Le quiz généré est invalide. Ajoutez au moins une question.');
                return $this->redirectToRoute('teacher_create_quiz', ['id' => $lesson->getId()]);
            }

            $jsonPath = $fileStorageService->storeQuizJson(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $quizService->createQuiz(
                $lesson,
                (string) $request->request->get('dynamic_title', ''),
                $jsonPath,
                (string) $request->request->get('dynamic_description', '')
            );

            $this->addFlash('success', 'Quiz généré et enregistré.');
            return $this->redirectToRoute('teacher_dashboard');
        }

        if ($uploadForm->isSubmitted() && $uploadForm->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $uploadForm->get('jsonFile')->getData();
            $content = file_get_contents($file->getPathname());
            $decoded = json_decode((string) $content, true);
            if (!$this->validateQuizStructure($decoded)) {
                $this->addFlash('error', 'Structure JSON invalide. Requis: passing_score (int), questions (array: question, choices[], answer).');
                return $this->redirectToRoute('teacher_create_quiz', ['id' => $lesson->getId()]);
            }

            $jsonPath = $fileStorageService->storeQuizJson((string) $content);
            $quizService->createQuiz(
                $lesson,
                (string) $uploadForm->get('title')->getData(),
                $jsonPath,
                (string) $uploadForm->get('description')->getData()
            );

            $this->addFlash('success', 'Quiz importé depuis JSON.');
            return $this->redirectToRoute('teacher_dashboard');
        }

        return $this->render('teacher/quiz_builder.html.twig', [
            'lesson' => $lesson,
            'uploadForm' => $uploadForm->createView(),
        ]);
    }

    #[Route('/teacher/quiz/{id}/edit', name: 'teacher_edit_quiz', methods: ['GET', 'POST'])]
    public function editQuiz(
        Request $request,
        Quiz $quiz,
        QuizService $quizService,
        FileStorageService $fileStorageService
    ): Response {
        $this->ensureTeacherOwnsCourse($quiz->getLesson()?->getCourse());

        if ($request->isMethod('POST')) {
             if ($this->isCsrfTokenValid('teacher_edit_quiz_'.$quiz->getId(), (string) $request->request->get('_token'))) {
                 
                 $jsonPath = null;
                 // Handle JSON File Replacement
                 $file = $request->files->get('json_file');
                 if ($file) {
                    $content = file_get_contents($file->getPathname());
                    $decoded = json_decode((string) $content, true);
                    if (!$this->validateQuizStructure($decoded)) {
                        $this->addFlash('error', 'Structure JSON invalide.');
                        return $this->redirectToRoute('teacher_edit_quiz', ['id' => $quiz->getId()]);
                    }
                    $jsonPath = $fileStorageService->storeQuizJson((string) $content);
                 } elseif ($request->request->has('dynamic_quiz_json')) {
                     // Handle Dynamic Generator Replacement
                     $rawJson = (string) $request->request->get('dynamic_quiz_json');
                     $decoded = json_decode($rawJson, true);
                     if ($decoded && $this->validateQuizStructure($decoded)) {
                        $jsonPath = $fileStorageService->storeQuizJson($rawJson);
                     }
                 }

                 $quizService->updateQuiz(
                     $quiz,
                     (string) $request->request->get('title'),
                     $jsonPath,
                     (string) $request->request->get('description')
                 );

                 $this->addFlash('success', 'Quiz mis à jour.');
                 return $this->redirectToRoute('teacher_edit_course', ['id' => $quiz->getLesson()->getCourse()->getId()]);
             }
        }

        $definition = $quizService->loadQuizDefinition($quiz);

        return $this->render('teacher/edit_quiz.html.twig', [
            'quiz' => $quiz,
            'existingQuestions' => $definition['questions'] ?? [],
        ]);
    }

    #[Route('/teacher/quiz/{id}/download', name: 'teacher_download_quiz', methods: ['GET'])]
    public function downloadQuizJson(Quiz $quiz): Response
    {
        $this->ensureTeacherOwnsCourse($quiz->getLesson()?->getCourse());
        
        // Construct absolute path. We might need a helper or parameter for public dir.
        // Assuming jsonPath is relative to public/
        // QuizService stores it as quizzes/filename.json which is relative to public/
        
        $filePath = $this->getParameter('kernel.project_dir').'/public/'.$quiz->getJsonPath();
        
        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Fichier JSON introuvable');
        }

        return $this->file($filePath, sprintf('quiz_%d.json', $quiz->getId()), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/teacher/quiz/{id}/test', name: 'teacher_test_quiz', methods: ['GET', 'POST'])]
    public function testQuiz(
        Request $request,
        Quiz $quiz,
        QuizService $quizService
    ): Response {
        $this->ensureTeacherOwnsCourse($quiz->getLesson()?->getCourse());
        
        $definition = $quizService->loadQuizDefinition($quiz);
        $questions = $definition['questions'] ?? [];

        if ($request->isMethod('POST')) {
            $answers = $request->request->all('answers');
            $result = $quizService->evaluateQuiz($quiz, $answers);

            $this->addFlash('info', sprintf('Résultat du test : %d%% (%s). Aucune tentative n\'a été enregistrée.', $result['score'], $result['passed'] ? 'Succès' : 'Échec'));
            
            return $this->redirectToRoute('teacher_edit_course', ['id' => $quiz->getLesson()->getCourse()->getId()]);
        }

        return $this->render('teacher/test_quiz.html.twig', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    #[Route('/teacher/quiz/{id}/delete', name: 'teacher_delete_quiz', methods: ['POST'])]
    public function deleteQuiz(
        Request $request,
        Quiz $quiz,
        QuizService $quizService
    ): RedirectResponse {
        $this->ensureTeacherOwnsCourse($quiz->getLesson()?->getCourse());

        if ($this->isCsrfTokenValid('teacher_delete_quiz_'.$quiz->getId(), (string) $request->request->get('_token'))) {
            $quizService->deleteQuiz($quiz);
            $this->addFlash('success', 'Quiz supprimé.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('teacher_edit_course', ['id' => $quiz->getLesson()->getCourse()->getId()]);
    }

    #[Route('/teacher/resource/{id}/download', name: 'teacher_download_resource', methods: ['GET'])]
    public function downloadResource(LessonResource $resource): Response
    {
        $this->ensureTeacherOwnsCourse($resource->getLesson()?->getCourse());
        $filePath = $this->getParameter('kernel.project_dir').'/public/'.$resource->getFilePath();
        if (!is_file($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable');
        }

        return $this->file($filePath, basename($filePath), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/teacher/resource/{id}', name: 'teacher_view_resource', methods: ['GET'])]
    public function viewResource(LessonResource $resource): Response
    {
        $this->ensureTeacherOwnsCourse($resource->getLesson()?->getCourse());
        return $this->render('teacher/resource_view.html.twig', [
            'resourceItem' => $resource,
        ]);
    }

    private function validateQuizStructure(array $data): bool
    {
        if (!isset($data['passing_score']) || !is_numeric($data['passing_score'])) {
            return false;
        }
        if (!isset($data['questions']) || !is_array($data['questions'])) {
            return false;
        }
        foreach ($data['questions'] as $q) {
            if (!isset($q['question']) || !is_string($q['question'])) return false;
            if (!isset($q['choices']) || !is_array($q['choices']) || count($q['choices']) < 2) return false;
            if (!isset($q['answer']) || !is_numeric($q['answer'])) return false;
        }
        return true;
    }

    private function ensureTeacherOwnsCourse(?Course $course): void
    {
        $this->denyAccessUnlessGranted('ROLE_TEACHER');

        if (!$course || $course->getTeacher() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only manage your own courses.');
        }
    }
}
