<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private string $uploadsDirectory,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(UploadedFile $file, string $directory = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadPath = $this->uploadsDirectory;
        if ($directory) {
            $uploadPath .= '/' . $directory;
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
        }

        try {
            $file->move($uploadPath, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload du fichier : ' . $e->getMessage());
        }

        return $fileName;
    }

    public function delete(string $fileName, string $directory = ''): bool
    {
        $filePath = $this->uploadsDirectory;
        if ($directory) {
            $filePath .= '/' . $directory;
        }
        $filePath .= '/' . $fileName;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadsDirectory;
    }
}