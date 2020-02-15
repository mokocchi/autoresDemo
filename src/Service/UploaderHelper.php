<?php

namespace App\Service;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    private $filesystem;
    private $logger;
    private $publicAssetBaseUrl;

    const PLANOS = 'planos';

    public function __construct(FilesystemInterface $publicUploadsFilesystem, LoggerInterface $logger, string $uploadedAssetsBaseUrl)
    {
        $this->filesystem = $publicUploadsFilesystem;
        $this->logger = $logger;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
    }

    public function getPublicPath(string $path)
    {
        return $this->publicAssetBaseUrl . $path;
    }

    public function uploadPlano(File $file, string $name, bool $exists)
    {
        if ($exists) {
            try {
                $result = $this->filesystem->delete(self::PLANOS . '/' . $name . ".png",);
                if ($result === false) {
                    throw new \Exception(sprintf('No se pudo borrar el archivo "%s.png"', $name));
                }

            } catch (FileNotFoundException $e) {
                $this->logger->alert(sprintf('No se encontró el archivo para borrar: "%s.png"', $name));
            }
        }

        $stream = fopen($file->getPathname(), 'r');
        $result = $this->filesystem->writeStream(
            self::PLANOS . '/' . $name . ".png",
            $stream
        );

        if ($result === false) {
            throw new \Exception(sprintf('No se pudo escribir el archivo subido "%s.png"', $name));
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
