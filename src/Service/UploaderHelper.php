<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    private $uploadsPath;

    const PLANOS = 'planos';

    public function __construct(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    public function getPublicPath(string $path)
    {
        return 'uploads/' . $path;
    }

    public function uploadPlano(UploadedFile $uploadedFile, string $name)
    {
        $destination = $this->uploadsPath . '/' . self::PLANOS;
        $uploadedFile->move(
            $destination,
            $name . ".png"
        );
    }
}
