<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TareaRepository")
 */
class Tarea
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
    private $consigna;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Dominio")
     * @ORM\JoinColumn(nullable=true)
     */
    private $dominio;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TipoTarea")
     * @ORM\JoinColumn(nullable=true)
     */
    private $tipo;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $extra = [];

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

    public function getConsigna(): ?string
    {
        return $this->consigna;
    }

    public function setConsigna(string $consigna): self
    {
        $this->consigna = $consigna;

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

    public function getTipo(): ?TipoTarea
    {
        return $this->tipo;
    }

    public function setTipo(?TipoTarea $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }
}
