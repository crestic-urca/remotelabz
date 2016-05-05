<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Device
 *
 * @ORM\Table(name="device")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DeviceRepository")
 */
class Device
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="propriete", type="string", length=255)
     */
    private $propriete;

    /**
     * @var string
     *
     * @ORM\Column(name="modele", type="string", length=255)
     */
    private $modele;

    /**
     * @var string
     *
     * @ORM\Column(name="marque", type="string", length=255)
     */
    private $marque;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Systeme")
     */
    private $systeme;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Network_Interface")
     */
    private $interfaceControle;
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return Device
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Device
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set propriete
     *
     * @param string $propriete
     *
     * @return Device
     */
    public function setPropriete($propriete)
    {
        $this->propriete = $propriete;

        return $this;
    }

    /**
     * Get propriete
     *
     * @return string
     */
    public function getPropriete()
    {
        return $this->propriete;
    }

    /**
     * Set modele
     *
     * @param string $modele
     *
     * @return Device
     */
    public function setModele($modele)
    {
        $this->modele = $modele;

        return $this;
    }

    /**
     * Get modele
     *
     * @return string
     */
    public function getModele()
    {
        return $this->modele;
    }

    /**
     * Set marque
     *
     * @param string $marque
     *
     * @return Device
     */
    public function setMarque($marque)
    {
        $this->marque = $marque;

        return $this;
    }

    /**
     * Get marque
     *
     * @return string
     */
    public function getMarque()
    {
        return $this->marque;
    }

    /**
     * Set systeme
     *
     * @param \AppBundle\Entity\Systeme $systeme
     *
     * @return Device
     */
    public function setSysteme(\AppBundle\Entity\Systeme $systeme = null)
    {
        $this->systeme = $systeme;

        return $this;
    }

    /**
     * Get systeme
     *
     * @return \AppBundle\Entity\Systeme
     */
    public function getSysteme()
    {
        return $this->systeme;
    }

    /**
     * Set interfaceControle
     *
     * @param \AppBundle\Entity\Network_Interface $interfaceControle
     *
     * @return Device
     */
    public function setInterfaceControle(\AppBundle\Entity\Network_Interface $interfaceControle = null)
    {
        $this->interfaceControle = $interfaceControle;

        return $this;
    }

    /**
     * Get interfaceControle
     *
     * @return \AppBundle\Entity\Network_Interface
     */
    public function getInterfaceControle()
    {
        return $this->interfaceControle;
    }
}
