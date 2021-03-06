<?php

namespace App\Service\VPN;

abstract class AbstractVPNConfigurationGenerator implements VPNConfiguratorGeneratorInterface
{
    protected $country;

    protected $province;

    protected $city;

    protected $organization;

    protected $email;

    protected $CACert;

    protected $exportPath;

    protected $commonName;

    protected $CAKey;

    protected $CAKeyPassphrase;

    protected $TLSKey;

    protected $remote;

    protected $validity;

    public function __construct(
        string $commonName,
        string $country,
        string $province,
        string $city,
        string $organization,
        string $email,
        string $CACert,
        string $CAKey,
        string $CAKeyPassphrase,
        string $TLSKey,
        string $exportPath,
        int $validity,
        string $remote
    ) {
        $this->country = $country;
        $this->province = $province;
        $this->city = $city;
        $this->organization = $organization;
        $this->email = $email;
        $this->CACert = $CACert;
        $this->exportPath = $exportPath;
        $this->commonName = $commonName;
        $this->CAKey = $CAKey;
        $this->CAKeyPassphrase = $CAKeyPassphrase;
        $this->TLSKey = $TLSKey;
        $this->remote = $remote;
        $this->validity = $validity;
    }

    abstract public function generate(&$privateKey, &$certificate);

    abstract public function generateConfig(string $privateKey, string $certificate): string;

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): VPNConfiguratorGeneratorInterface
    {
        $this->country = $country;

        return $this;
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function setProvince(string $province): VPNConfiguratorGeneratorInterface
    {
        $this->province = $province;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): VPNConfiguratorGeneratorInterface
    {
        $this->city = $city;

        return $this;
    }

    public function getOrganization(): string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): VPNConfiguratorGeneratorInterface
    {
        $this->organization = $organization;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): VPNConfiguratorGeneratorInterface
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCACert(): string
    {
        return $this->CACert;
    }

    /**
     * @inheritdoc
     */
    public function setCACert(string $CACert): VPNConfiguratorGeneratorInterface
    {
        $this->CACert = $CACert;

        return $this;
    }

    public function getExportPath(): string
    {
        return $this->exportPath;
    }

    public function setExportPath(string $exportPath): VPNConfiguratorGeneratorInterface
    {
        $this->exportPath = $exportPath;

        return $this;
    }

    public function getCommonName(): string
    {
        return $this->commonName;
    }

    public function setCommonName(string $commonName): VPNConfiguratorGeneratorInterface
    {
        $this->commonName = $commonName;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCAKey(): string
    {
        return $this->CAKey;
    }

    /**
     * @inheritdoc
     */
    public function setCAKey(string $CAKey): VPNConfiguratorGeneratorInterface
    {
        $this->CAKey = $CAKey;

        return $this;
    }

    /**
     * Get the CA key passphrase.
     */
    public function getCAKeyPassphrase(): string
    {
        return $this->CAKeyPassphrase;
    }

    /**
     * Set the CA key passphrase.
     */
    public function setCAKeyPassphrase(string $CAKeyPassphrase): VPNConfiguratorGeneratorInterface
    {
        $this->CAKeyPassphrase = $CAKeyPassphrase;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTLSKey(): string
    {
        return $this->TLSKey;
    }

    /**
     * @inheritdoc
     */
    public function setTLSKey(string $TLSKey): VPNConfiguratorGeneratorInterface
    {
        $this->TLSKey = $TLSKey;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRemote(): string
    {
        return $this->remote;
    }

    /**
     * @inheritdoc
     */
    public function setRemote(string $remote): VPNConfiguratorGeneratorInterface
    {
        $this->remote = $remote;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValidity(): int
    {
        return $this->validity;
    }

    /**
     * @inheritdoc
     */
    public function setValidity(int $validity): VPNConfiguratorGeneratorInterface
    {
        $this->validity = $validity;

        return $this;
    }
}
