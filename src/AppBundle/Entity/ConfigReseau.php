<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigReseau
 *
 * @ORM\Table(name="configReseau")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConfigReseauRepository")
 */
class ConfigReseau
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
     * @ORM\Column(name="IP", type="string", length=255,nullable=true)
     */
    private $iP;

    /**
     * @var string
     *
     * @ORM\Column(name="IPv6", type="string", length=255, nullable=true)
     */
    private $iPv6;

    /**
     * @var string
     *
     * @ORM\Column(name="IP_DNS", type="string", length=255, nullable=true)
     */
    private $iPDNS;

    /**
     * @var string
     *
     * @ORM\Column(name="IP_Gateway", type="string", length=255, nullable=true)
     */
    private $iPGateway;

    /**
     * @var string
     *
     * @ORM\Column(name="Masque", type="string", length=255,nullable=true)
     */
    private $masque;

    /**
     * @var string
     *
     * @ORM\Column(name="Protocole", type="string", length=255, nullable=true)
     */
    private $protocole;


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
     * Set iP
     *
     * @param string $iP
     *
     * @return ConfigReseau
     */
    public function setIP($iP)
    {
        $this->iP = $iP;

        return $this;
    }

    /**
     * Get iP
     *
     * @return string
     */
    public function getIP()
    {
        return $this->iP;
    }

    /**
     * Set iPv6
     *
     * @param string $iPv6
     *
     * @return ConfigReseau
     */
    public function setIPv6($iPv6)
    {
        $this->iPv6 = $iPv6;

        return $this;
    }

    /**
     * Get iPv6
     *
     * @return string
     */
    public function getIPv6()
    {
        return $this->iPv6;
    }

    /**
     * Set iPDNS
     *
     * @param string $iPDNS
     *
     * @return ConfigReseau
     */
    public function setIPDNS($iPDNS)
    {
        $this->iPDNS = $iPDNS;

        return $this;
    }

    /**
     * Get iPDNS
     *
     * @return string
     */
    public function getIPDNS()
    {
        return $this->iPDNS;
    }

    /**
     * Set iPGateway
     *
     * @param string $iPGateway
     *
     * @return ConfigReseau
     */
    public function setIPGateway($iPGateway)
    {
        $this->iPGateway = $iPGateway;

        return $this;
    }

    /**
     * Get iPGateway
     *
     * @return string
     */
    public function getIPGateway()
    {
        return $this->iPGateway;
    }

    /**
     * Set masque
     *
     * @param string $masque
     *
     * @return ConfigReseau
     */
    public function setMasque($masque)
    {
        $this->masque = $masque;

        return $this;
    }

    /**
     * Get masque
     *
     * @return string
     */
    public function getMasque()
    {
        return $this->masque;
    }

    /**
     * Set protocole
     *
     * @param string $protocole
     *
     * @return ConfigReseau
     */
    public function setProtocole($protocole)
    {
        $this->protocole = $protocole;

        return $this;
    }

    /**
     * Get protocole
     *
     * @return string
     */
    public function getProtocole()
    {
        return $this->protocole;
    }
}
