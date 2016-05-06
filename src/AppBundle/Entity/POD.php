<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * POD
 *
 * @ORM\Table(name="pod")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PODRepository")
 */
class POD
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
     *  @ORM\OneToMany(targetEntity="AppBundle\Entity\Device", mappedBy="pod")
     */

    private $devices;

    /**
     * @var string
     *
     * @ORM\Column(name="NomDevice2", type="string", length=255)
     */
    private $NomDevice;

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
     * Constructor
     */
    public function __construct()
    {
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add device
     *
     * @param \AppBundle\Entity\Device $device
     *
     * @return POD
     */
    public function addDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices[] = $device;
        $device->setPod(this);

        return $this;
    }

    /**
     * Remove device
     *
     * @param \AppBundle\Entity\Device $device
     */
    public function removeDevice(\AppBundle\Entity\Device $device)
    {
        $this->devices->removeElement($device);
    }

    /**
     * Get devices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * Set nomDevice
     *
     * @param string $nomDevice
     *
     * @return POD
     */
    public function setNomDevice($nomDevice)
    {
        $this->NomDevice = $nomDevice;

        return $this;
    }

    /**
     * Get nomDevice
     *
     * @return string
     */
    public function getNomDevice()
    {
        return $this->NomDevice;
    }
}
