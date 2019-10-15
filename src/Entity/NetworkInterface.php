<?php

namespace App\Entity;

use App\Utils\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Instance\InstanciableInterface;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NetworkInterfaceRepository")
 * @Serializer\XmlRoot("network_interface")
 * @UniqueEntity(
 *     fields="macAddress",
 *     errorPath="macAddress",
 *     message="This MAC address is already used by another interface."
 * )
 */
class NetworkInterface implements InstanciableInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\XmlAttribute
     * @Serializer\Groups({"primary_key"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\XmlAttribute
     * @Serializer\Groups({"network_interfaces", "lab", "start_lab", "stop_lab"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\XmlAttribute
     * @Serializer\Groups({"network_interfaces", "lab", "start_lab", "stop_lab"})
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\NetworkSettings", cascade={"persist", "remove"})
     * @Serializer\XmlList(entry="network_settings")
     * @Serializer\Groups({"network_interfaces", "lab", "start_lab", "stop_lab"})
     */
    private $settings;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Device", inversedBy="networkInterfaces", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * @Serializer\Groups({"network_interfaces", "lab"})
     */
    private $device;

    /**
     * @ORM\Column(type="string", length=17)
     * @Serializer\XmlAttribute
     * @Serializer\Groups({"network_interfaces", "lab", "start_lab", "stop_lab"})
     * @Assert\Regex("/^[a-fA-F0-9:]{17}$/")
     */
    private $macAddress;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NetworkInterfaceInstance", mappedBy="networkInterface", cascade={"persist", "remove"})
     * @Serializer\XmlList(inline=true, entry="instance")
     * @Serializer\Groups({"lab"})
     */
    private $instances;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\XmlAttribute
     * @Serializer\Groups({"network_interfaces", "lab", "start_lab", "stop_lab"})
     */
    private $uuid;

    const TYPE_TAP = 'tap';
    const TYPE_OVS = 'ovs';

    public function __construct()
    {
        $this->instances = new ArrayCollection();
        $this->uuid = (string) new Uuid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
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

    public function getSettings(): ?NetworkSettings
    {
        return $this->settings;
    }

    public function setSettings(?NetworkSettings $settings): self
    {
        $this->settings = $settings;

        return $this;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    public function setMacAddress(string $macAddress): self
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    /**
     * @return Collection|Instance[]
     */
    public function getInstances(): Collection
    {
        return $this->instances;
    }

    public function getUserInstance(User $user): ?Instance
    {
        $instance = $this->instances->filter(function ($value) use ($user) {
            return $value->getUser() == $user;
        });
        
        if (is_null($instance)) {
            return null;
        }
        
        return $instance[0];
    }

    public function addInstance(Instance $instance): self
    {
        if (!$this->instances->contains($instance)) {
            $this->instances[] = $instance;
            $instance->setNetworkInterface($this);
        }

        return $this;
    }

    public function removeInstance(Instance $instance): self
    {
        if ($this->instances->contains($instance)) {
            $this->instances->removeElement($instance);
            // set the owning side to null (unless already changed)
            if ($instance->getNetworkInterface() === $this) {
                $instance->setNetworkInterface(null);
            }
        }

        return $this;
    }

    public function setInstances(array $instances): self
    {
        $this->getInstances()->clear();
        foreach ($instances as $instance) {
            $this->addInstance($instance);
        }

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }
}
