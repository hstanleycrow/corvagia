<?php

declare(strict_types=1);

namespace App\Core\ImageUploader;

use App\Core\ImageUploader\ImageUploader;

class ImageUploaderClient implements ImageUploaderInterface
{

    public function __construct(protected ImageUploader $imageUploader, private string $inputName)
    {
    }

    public function upload(): string
    {
        return $this->imageUploader->upload($this->inputName);
    }

    public function isUploading(): bool
    {
        return !empty($_FILES[$this->inputName]['name']);
    }
}
