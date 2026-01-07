<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileStorageService
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    public function storeResource(UploadedFile $file): string
    {
        $relativeDir = 'uploads/resources';
        $absoluteDir = $this->projectDir.'/public/'.$relativeDir;
        $this->filesystem->mkdir($absoluteDir);

        $uniqueName = sprintf('%s_%s.%s', uniqid('res_', true), pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), $file->guessExtension() ?: $file->getClientOriginalExtension());
        $file->move($absoluteDir, $uniqueName);

        return $relativeDir.'/'.$uniqueName;
    }

    public function storeQuizJson(string $jsonContent): string
    {
        $relativeDir = 'quizzes';
        $absoluteDir = $this->projectDir.'/public/'.$relativeDir;
        $this->filesystem->mkdir($absoluteDir);

        $fileName = sprintf('quiz_%s.json', uniqid());
        $this->filesystem->dumpFile($absoluteDir.'/'.$fileName, $jsonContent);

        return $relativeDir.'/'.$fileName;
    }

    public function storeCoursePicture(UploadedFile $file): string
    {
        $relativeDir = 'uploads/courses';
        $absoluteDir = $this->projectDir.'/public/'.$relativeDir;
        $this->filesystem->mkdir($absoluteDir);

        $uniqueName = sprintf('%s_%s.%s', uniqid('course_', true), pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), $file->guessExtension() ?: 'jpg');
        $file->move($absoluteDir, $uniqueName);

        return $relativeDir.'/'.$uniqueName;
    }
}
