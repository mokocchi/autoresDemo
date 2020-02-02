<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ActividadRepository")
 */
class Actividad
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nombre;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $objetivo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Idioma")
     * @ORM\JoinColumn(nullable=true)
     */
    private $idioma;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dominio")
     * @ORM\JoinColumn(nullable=true)
     */
    private $dominio;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TipoPlanificacion")
     * @ORM\JoinColumn(nullable=true)
     */
    private $tipoPlanificacion;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tarea")
     */
    private $tareas;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Planificacion", cascade={"persist", "remove"})
     */
    private $planificacion;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Usuario", inversedBy="actividadesCreadas")
     * @ORM\JoinColumn(nullable=true)
     */
    private $autor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Estado")
     * @ORM\JoinColumn(nullable=true)
     */
    private $estado;

    public function __construct()
    {
        $this->tareas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getObjetivo(): ?string
    {
        return $this->objetivo;
    }

    public function setObjetivo(string $objetivo): self
    {
        $this->objetivo = $objetivo;

        return $this;
    }

    public function getIdioma(): ?Idioma
    {
        return $this->idioma;
    }

    public function setIdioma(?Idioma $idioma): self
    {
        $this->idioma = $idioma;

        return $this;
    }

    public function getDominio(): ?Dominio
    {
        return $this->dominio;
    }

    public function setDominio(?Dominio $dominio): self
    {
        $this->dominio = $dominio;

        return $this;
    }

    public function getTipoPlanificacion(): ?TipoPlanificacion
    {
        return $this->tipoPlanificacion;
    }

    public function setTipoPlanificacion(?TipoPlanificacion $tipoPlanificacion): self
    {
        $this->tipoPlanificacion = $tipoPlanificacion;

        return $this;
    }

    /**
     * @return Collection|Tarea[]
     */
    public function getTareas(): Collection
    {
        return $this->tareas;
    }

    public function addTarea(Tarea $tarea): self
    {
        if (!$this->tareas->contains($tarea)) {
            $this->tareas[] = $tarea;
        }

        return $this;
    }

    public function removeTarea(Tarea $tarea): self
    {
        if ($this->tareas->contains($tarea)) {
            $this->tareas->removeElement($tarea);
        }

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

    public function getAutor(): ?Usuario
    {
        return $this->autor;
    }

    public function setAutor(?Usuario $autor): self
    {
        $this->autor = $autor;

        return $this;
    }

    public function getEstado(): ?Estado
    {
        return $this->estado;
    }

    public function setEstado(?Estado $estado): self
    {
        $this->estado = $estado;

        return $this;
    }
}
