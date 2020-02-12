<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SaltoRepository")
 * @ExclusionPolicy("all")
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
     * @Expose
     * @Groups({"publico", "autor"})
     */
    private $respuesta;

    /**
     * @ORM\Column(type="string", length=255)
     * @Expose
     * @Groups({"publico", "autor"})
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

    /**
     * @VirtualProperty(name="destino_ids") 
     * @Expose
     * @Groups({"publico", "autor"})
     */
    public function getDestinoIds(): Collection
    {
        return $this->destino->map(function ($elem) {
            return $elem->getId();
        });
    }

    public function getDestinoCodes()
    {
        $destinoCodes = [];
        foreach ($this->destino as $destino) {
            $destinoCodes[] = $destino->getCodigo();
        }
        return $destinoCodes;
    }


    /**
     * @VirtualProperty(name="origen_id") 
     * @Expose
     * @Groups({"publico", "autor"})
     */
    public function getOrigenIds(): ?int
    {
        return $this->origen->getId();
    }
}
