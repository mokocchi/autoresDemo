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
 * @ORM\Entity(repositoryClass="App\Repository\PlanificacionRepository")
 * @ExclusionPolicy("all")
 */
class Planificacion
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Salto", mappedBy="planificacion")
     * @Expose
     * @Groups({"publico", "autor"})
     */
    private $saltos;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     * @ORM\JoinTable(name="tarea_opcional")
     */
    private $opcionales;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     * @ORM\JoinTable(name="tarea_inicial")
     */
    private $iniciales;

    public function __construct()
    {
        $this->saltos = new ArrayCollection();
        $this->opcionales = new ArrayCollection();
        $this->iniciales = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Salto[]
     */
    public function getSaltos(): Collection
    {
        return $this->saltos;
    }

    public function addSalto(Salto $salto): self
    {
        if (!$this->saltos->contains($salto)) {
            $this->saltos[] = $salto;
            $salto->setPlanificacion($this);
        }

        return $this;
    }

    public function removeSalto(Salto $salto): self
    {
        if ($this->saltos->contains($salto)) {
            $this->saltos->removeElement($salto);
            // set the owning side to null (unless already changed)
            if ($salto->getPlanificacion() === $this) {
                $salto->setPlanificacion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Tarea[]
     */
    public function getOpcionales(): Collection
    {
        return $this->opcionales;
    }

    public function addOpcional(Tarea $opcional): self
    {
        if (!$this->opcionales->contains($opcional)) {
            $this->opcionales[] = $opcional;
        }

        return $this;
    }

    public function removeOpcional(Tarea $opcional): self
    {
        if ($this->opcionales->contains($opcional)) {
            $this->opcionales->removeElement($opcional);
        }

        return $this;
    }

    /**
     * @return Collection|Tarea[]
     */
    public function getIniciales(): Collection
    {
        return $this->iniciales;
    }

    public function addInicial(Tarea $inicial): self
    {
        if (!$this->iniciales->contains($inicial)) {
            $this->iniciales[] = $inicial;
        }

        return $this;
    }

    public function removeInicial(Tarea $inicial): self
    {
        if ($this->iniciales->contains($inicial)) {
            $this->iniciales->removeElement($inicial);
        }

        return $this;
    }

     /**
     * @VirtualProperty(name="iniciales_ids") 
     * @Expose
     * @Groups({"publico", "autor"})
     */
    public function getInicialesIds() {
        return $this->iniciales->map(function ($elem) {
            return $elem->getId();
        });
    }

     /**
     * @VirtualProperty(name="opcionales_ids") 
     * @Expose
     * @Groups({"publico", "autor"})
     */
    public function getOpcionalesIds() {
        return $this->opcionales->map(function ($elem) {
            return $elem->getId();
        });
    }
}
