<?php

namespace App\Service;

use App\Entity\Attempt;
use App\Entity\Lesson;
use App\Entity\Quiz;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $quizStoragePath
    ) {
    }

    public function createQuiz(
        Lesson $lesson,
        string $title,
        string $jsonPath,
        ?string $description = null
    ): Quiz {
        $quiz = new Quiz();
        $quiz
            ->setLesson($lesson)
            ->setTitle($title)
            ->setJsonPath($jsonPath)
            ->setDescription($description);

        $this->entityManager->persist($quiz);
        $this->entityManager->flush();

        return $quiz;
    }

    public function gradeQuiz(Quiz $quiz, User $student, array $answers): Attempt
    {
        $definition = $this->loadQuizDefinition($quiz);

        $questions = $definition['questions'] ?? [];
        $passingScore = (int) ($definition['passing_score'] ?? 0);
        $totalQuestions = count($questions);

        $correct = 0;
        foreach ($questions as $index => $question) {
            $expected = $question['answer'] ?? null;
            if (isset($answers[$index]) && (int) $answers[$index] === (int) $expected) {
                $correct++;
            }
        }

        $score = $totalQuestions > 0 ? (int) round(($correct / $totalQuestions) * 100) : 0;
        $passed = $score >= $passingScore;

        $attempt = new Attempt();
        $attempt
            ->setStudent($student)
            ->setQuiz($quiz)
            ->setScore($score)
            ->setPassed($passed)
            ->setAttemptedAt(new \DateTimeImmutable());

        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        return $attempt;
    }

    public function evaluateQuiz(Quiz $quiz, array $answers): array
    {
        $definition = $this->loadQuizDefinition($quiz);

        $questions = $definition['questions'] ?? [];
        $passingScore = (int) ($definition['passing_score'] ?? 0);
        $totalQuestions = count($questions);

        $correct = 0;
        foreach ($questions as $index => $question) {
            $expected = $question['answer'] ?? null;
            if (isset($answers[$index]) && (int) $answers[$index] === (int) $expected) {
                $correct++;
            }
        }

        $score = $totalQuestions > 0 ? (int) round(($correct / $totalQuestions) * 100) : 0;
        $passed = $score >= $passingScore;

        return ['score' => $score, 'passed' => $passed];
    }

    public function updateQuiz(
        Quiz $quiz,
        string $title,
        ?string $jsonPath,
        ?string $description
    ): Quiz {
        $quiz
            ->setTitle($title)
            ->setDescription($description);

        if ($jsonPath) {
            $quiz->setJsonPath($jsonPath);
        }

        $this->entityManager->flush();

        return $quiz;
    }

    public function deleteQuiz(Quiz $quiz): void
    {
        $this->entityManager->remove($quiz);
        $this->entityManager->flush();
    }

    /**
     * @return array<string,mixed>
     */
    public function loadQuizDefinition(Quiz $quiz): array
    {
        $path = $this->getAbsolutePath($quiz->getJsonPath());
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function storeQuizJson(string $jsonContent): string
    {
        $directory = rtrim($this->quizStoragePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'quizzes';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fileName = sprintf('quiz_%s.json', uniqid());
        $absolutePath = $directory.DIRECTORY_SEPARATOR.$fileName;
        file_put_contents($absolutePath, $jsonContent);

        return basename($directory).'/'.$fileName;
    }

    private function getAbsolutePath(?string $relativePath): string
    {
        $base = rtrim($this->quizStoragePath, DIRECTORY_SEPARATOR);
        return $base.DIRECTORY_SEPARATOR.ltrim((string) $relativePath, DIRECTORY_SEPARATOR);
    }
}
