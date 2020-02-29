<?php

namespace App\DataFixtures;

use App\Entity\Actividad;
use App\Entity\Dominio;
use App\Entity\Estado;
use App\Entity\Idioma;
use App\Entity\Planificacion;
use App\Entity\TipoPlanificacion;
use App\Entity\Usuario;
use Doctrine\Persistence\ObjectManager;

class ActividadesFixture extends BaseFixture
{

    /**
     * @param array $actividad_array Array of nombre, objetivo, codigo, dominio and maybe autor, maybe estado
     */
    protected function createActividad(array $actividad_array, $manager): Actividad
    {
        $actividad = new Actividad();
        $actividad->setNombre($actividad_array["nombre"]);
        $actividad->setObjetivo($actividad_array["objetivo"]);
        $actividad->setCodigo($actividad_array["codigo"]);
        $actividad->setDominio($actividad_array["dominio"]);
        $idioma = $manager->getRepository(Idioma::class)->findOneBy(["code" => "es"]);
        $actividad->setIdioma($idioma);
        $tipoPlanificacion = $manager->getRepository(TipoPlanificacion::class)->findOneBy(["nombre" => "Secuencial"]);
        $actividad->setTipoPlanificacion($tipoPlanificacion);
        $planificacion = new Planificacion();
        $manager->persist($planificacion);
        $actividad->setPlanificacion($planificacion);
        if (array_key_exists("estado", $actividad_array)) {
            $estado = $manager->getRepository(Estado::class)->findOneBy(["nombre" => $actividad_array["estado"]]);
        } else {
            $estado = $manager->getRepository(Estado::class)->findOneBy(["nombre" => "Privado"]);
        }
        $actividad->setEstado($estado);
        if (!array_key_exists("autor", $actividad_array)) {
            $autor = $manager->getRepository(Usuario::class)->findOneBy(["email" => "autor@autores.demo"]);
            $actividad->setAutor($autor);
        } else {
            $autor = $manager->getRepository(Usuario::class)->findOneBy(["email" => $actividad_array["autor"]]);
            $actividad->setAutor($autor);
        }
        $manager->persist($actividad);
        $manager->flush();
        return $actividad;
    }

    protected function loadData(ObjectManager $manager)
    {
        $dominio = new Dominio();
        $dominio->setNombre("Pruebas");
        $manager->persist($dominio);

        for ($i = 0; $i < 25; $i++) {
            $this->createActividad(array(
                "nombre" => sprintf("Actividad test %s", $i + 1),
                "codigo" => $this->faker->sha256,
                "objetivo" => "Probar la lista de actividades",
                "estado" => "Público",
                "dominio" => $dominio
            ), $manager);
        }
    }
}
