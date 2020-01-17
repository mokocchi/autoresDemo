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

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     * @ORM\JoinTable(name="tarea_opcional")
     */
    private $opcionales;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     * @ORM\JoinTable(name="tarea_inicial")
     */
    private $inciales;

    public function __construct()
    {
        $this->saltos = new ArrayCollection();
        $this->opcionales = new ArrayCollection();
        $this->inciales = new ArrayCollection();
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

    public function addOpcionale(Tarea $opcionale): self
    {
        if (!$this->opcionales->contains($opcionale)) {
            $this->opcionales[] = $opcionale;
        }

        return $this;
    }

    public function removeOpcionale(Tarea $opcionale): self
    {
        if ($this->opcionales->contains($opcionale)) {
            $this->opcionales->removeElement($opcionale);
        }

        return $this;
    }

    /**
     * @return Collection|Tarea[]
     */
    public function getInciales(): Collection
    {
        return $this->inciales;
    }

    public function addInciale(Tarea $inciale): self
    {
        if (!$this->inciales->contains($inciale)) {
            $this->inciales[] = $inciale;
        }

        return $this;
    }

    public function removeInciale(Tarea $inciale): self
    {
        if ($this->inciales->contains($inciale)) {
            $this->inciales->removeElement($inciale);
        }

        return $this;
    }
}
