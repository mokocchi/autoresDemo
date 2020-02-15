<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Tag;
use App\Entity\Tarea;
use App\Entity\TipoTarea;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TareaFixtures extends BaseFixture
{
    private static $nombresTarea = [
        'Números Prueba',
        'Palabras Prueba',
        'Sonidos Prueba',
    ];

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(5, 'main_tareas', function($count) use ($manager) {
            $tarea = new Tarea();
            $tarea->setNombre($this->faker->randomElement(self::$nombresTarea))
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

            $tarea->setCodigo($this->faker->md5);

            return $tarea;
        });

        $manager->flush();
    }
}
