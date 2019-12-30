<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlanificacionRepository")
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
     */
    private $saltos;

    public function __construct()
    {
        $this->saltos = new ArrayCollection();
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
}
