<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LabRepository")
 */
class Lab
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
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\POD")
     * @ORM\JoinColumn(nullable=false)
     */
    private $pod;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Connexion", inversedBy="labs")
     */
    private $connexions;

    public function __construct()
    {
        $this->connexions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPod(): ?POD
    {
        return $this->pod;
    }

    public function setPod(?POD $pod): self
    {
        $this->pod = $pod;

        return $this;
    }

    /**
     * @return Collection|Connexion[]
     */
    public function getConnexions(): Collection
    {
        return $this->connexions;
    }

    public function addConnexion(Connexion $connexion): self
    {
        if (!$this->connexions->contains($connexion)) {
            $this->connexions[] = $connexion;
        }

        return $this;
    }

    public function removeConnexion(Connexion $connexion): self
    {
        if ($this->connexions->contains($connexion)) {
            $this->connexions->removeElement($connexion);
        }

        return $this;
    }
}
