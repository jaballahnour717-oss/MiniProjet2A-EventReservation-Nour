<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private readonly string           $uploadDir,
        private readonly SluggerInterface $slugger,
    ) {}

    /**
     * Upload un fichier et retourne le nom de fichier généré
     */
    public function upload(UploadedFile $file, string $subDir = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $targetDir = $this->uploadDir . ($subDir ? '/' . $subDir : '');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        try {
            $file->move($targetDir, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Impossible d\'uploader le fichier : ' . $e->getMessage());
        }

        return ($subDir ? $subDir . '/' : '') . $newFilename;
    }

    /**
     * Supprime un fichier uploadé
     */
    public function delete(string $filename): void
    {
        $path = $this->uploadDir . '/' . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function getPublicPath(string $filename): string
    {
        return '/uploads/' . $filename;
    }
}