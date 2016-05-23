<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Network_Interface
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Network_InterfaceRepository")
 * @UniqueEntity(fields="nom",message="une interafce existe dèjà avec ce nom")
 */
class Network_Interface
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
     * @ORM\Column(name="nom_interface", type="string", length=255,unique = true)
     *  @Assert\NotBlank()
     */
    private $nom;

    /**
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\ConfigReseau", cascade="persist")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */

    private $config_reseau;
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Device",inversedBy="network_interfaces")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $device;

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
     * (Add this method into your class)
     *
     * @return string String representation of this class
     */
    public function __toString()
    {
        return $this->nom;
    }
    /**
     * Set nom
     *
     * @param string $nom
     *
     * @return Network_Interface
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
     * Set configReseau
     *
     * @param \AppBundle\Entity\ConfigReseau $configReseau
     *
     * @return Network_Interface
     */
    public function setConfigReseau(\AppBundle\Entity\ConfigReseau $configReseau = null)
    {
        $this->config_reseau = $configReseau;

        return $this;
    }

    /**
     * Get configReseau
     *
     * @return \AppBundle\Entity\ConfigReseau
     */
    public function getConfigReseau()
    {
        return $this->config_reseau;
    }

    /**
     * Set device
     *
     * @param \AppBundle\Entity\Device $device
     *
     * @return Network_Interface
     */
    public function setDevice(\AppBundle\Entity\Device $device = null)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return \AppBundle\Entity\Device
     */
    public function getDevice()
    {
        return $this->device;
    }


}
