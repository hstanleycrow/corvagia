<?php

declare(strict_types=1);

namespace App\Core\ImageUploader;

class ImageUploaderBase
{
    public function __construct(protected ImageUploaderClient $ImageUploaderClient)
    {
    }

    public function upload(): string
    {
        $imagePath = '';
        try {
            $imagePath = $this->ImageUploaderClient->upload();
        } catch (\Exception $e) {
            $_SESSION['errors']['image'] = $e->getMessage();
        }
        if (empty($imagePath)) :
            $_SESSION['errors']['image'] = 'Debe seleccionar una imagen';
        endif;
        return $imagePath;
    }

    public function isUploading(): bool
    {
        return $this->ImageUploaderClient->isUploading();
    }
}
