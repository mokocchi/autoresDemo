<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
class Plano
{
    /**
     * @Assert\Image(
     *     maxSize = "2048k",
     * )
     */
    protected $plano;

    function getPlano() {
        return $this->plano;
    }

    function setPlano($plano) {
        $this->plano = $plano;
    }
}