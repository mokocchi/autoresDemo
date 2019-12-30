<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SaltoRepository")
 */
class Salto
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $respuesta;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $condicion;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Planificacion", inversedBy="saltos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $planificacion;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tarea")
     * @ORM\JoinColumn(nullable=false)
     */
    private $origen;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     */
    private $destino;

    public function __construct()
    {
        $this->destino = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRespuesta(): ?string
    {
        return $this->respuesta;
    }

    public function setRespuesta(?string $respuesta): self
    {
        $this->respuesta = $respuesta;

        return $this;
    }

    public function getCondicion(): ?string
    {
        return $this->condicion;
    }

    public function setCondicion(string $condicion): self
    {
        $this->condicion = $condicion;

        return $this;
    }

    public function getPlanificacion(): ?Planificacion
    {
        return $this->planificacion;
    }

    public function setPlanificacion(?Planificacion $planificacion): self
    {
        $this->planificacion = $planificacion;

        return $this;
    }

    public function getOrigen(): ?Tarea
    {
        return $this->origen;
    }

    public function setOrigen(?Tarea $origen): self
    {
        $this->origen = $origen;

        return $this;
    }

    /**
     * @return Collection|Tarea[]
     */
    public function getDestino(): Collection
    {
        return $this->destino;
    }

    public function addDestino(Tarea $destino): self
    {
        if (!$this->destino->contains($destino)) {
            $this->destino[] = $destino;
        }

        return $this;
    }

    public function removeDestino(Tarea $destino): self
    {
        if ($this->destino->contains($destino)) {
            $this->destino->removeElement($destino);
        }

        return $this;
    }
}
