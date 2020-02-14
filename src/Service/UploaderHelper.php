<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    private $uploadsPath;

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    public function uploadPlano(UploadedFile $uploadedFile, string $name)
    {
        $destination = $this->uploadsPath . '/planos';
        $uploadedFile->move(
            $destination,
            $name . ".png"
        );
        
    }
}
