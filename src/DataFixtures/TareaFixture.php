<?php

namespace App\DataFixtures;

use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Tarea;
use App\Entity\TipoTarea;
use App\Entity\Usuario;
use App\Service\UploaderHelper;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class TareaFixture extends BaseFixture
{
    private static $nombresTarea = [
        'Números Prueba',
        'Palabras Prueba',
        'Sonidos Prueba',
    ];

    private static $planosTarea = [
        'mesa1.png',
        'mesa2.png',
        'suelo.png'
    ];

    private $uploaderHelper;
    public function __construct(UploaderHelper $uploaderHelper)
    {
        $this->uploaderHelper = $uploaderHelper;
    }

    private function fakeUploadImage(string $codigo)
    {
        $randomImage = $this->faker->randomElement(self::$planosTarea);

        $fs = new Filesystem();
        $targetPath = sys_get_temp_dir() . '/' . $randomImage;
        $fs->copy(__DIR__ . '/images/' . $randomImage, $targetPath, true);

        $this->uploaderHelper->uploadPlano(new File($targetPath), $codigo, false);
    }

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(10, 'main_tareas', function ($count) use ($manager) {
            $tarea = new Tarea();
            $tarea->setNombre("Tarea prueba". ($count + 1))
                ->setConsigna("Tarea libre!");

            $tipoDeposito = $manager->getRepository(TipoTarea::class)->findOneBy(["codigo" => "deposit"]);
            $tarea->setTipo($tipoDeposito);

            $dominioPruebas = $manager->getRepository(Dominio::class)->findOneBy(["nombre" => "Pruebas"]);
            $tarea->setDominio($dominioPruebas);

            // publish most tareas
            $estadoRepository = $manager->getRepository(Estado::class);
            if ($this->faker->boolean(70)) {
                $publico = $estadoRepository->findOneBy(["nombre" => "Público"]);
                $tarea->setEstado($publico);
            } else {
                $privado = $estadoRepository->findOneBy(["nombre" => "Privado"]);
                $tarea->setEstado($privado);
            }

            $tarea->setCodigo($this->faker->sha256);

            $usuario = $manager->getRepository(Usuario::class)->findOneBy(["email" => "autor@autores.demo"]);
            $tarea->setAutor($usuario);

            $this->fakeUploadImage($tarea->getCodigo());

            return $tarea;
        });

        $manager->flush();
    }
}
