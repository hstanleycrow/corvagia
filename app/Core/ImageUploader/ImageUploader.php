<?php

declare(strict_types=1);

namespace App\Core\ImageUploader;

class ImageUploader
{
    private string $targetDir;
    private array $allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $webpQuality = 85;

    public function __construct(string $targetDir)
    {
        $this->targetDir = $targetDir;
    }

    public function upload(string $inputName): string
    {
        $originalName = $_FILES[$inputName]['name'];

        if (!$this->isValidFormat($originalName)) {
            throw new \Exception("Formato de archivo no válido. Se aceptan: jpg, jpeg, png, gif, webp");
        }

        $baseName     = pathinfo(slug($originalName), PATHINFO_FILENAME);
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $originalPath = $this->targetDir . $baseName . '.' . $extension;

        if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $originalPath)) {
            throw new \Exception("Error al mover el archivo subido");
        }

        // Generate the .webp next to the original (the original stays as fallback)
        $this->generateWebP($originalPath);

        return '/' . $originalPath;
    }

    private function generateWebP(string $sourcePath): void
    {
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $sourcePath);

        // Already a webp: nothing to convert
        if ($sourcePath === $webpPath) {
            return;
        }

        $mime  = mime_content_type($sourcePath);
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => $this->createFromPng($sourcePath),
            'image/gif'  => imagecreatefromgif($sourcePath),
            default      => throw new \Exception("Formato no soportado para conversión WebP: $mime"),
        };

        imagewebp($image, $webpPath, $this->webpQuality);
        imagedestroy($image);
    }

    private function createFromPng(string $filePath): \GdImage
    {
        $image = imagecreatefrompng($filePath);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        return $image;
    }

    private function isValidFormat(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, $this->allowedFormats);
    }
}
