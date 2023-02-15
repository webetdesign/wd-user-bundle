<?php


namespace WebEtDesign\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\UserBundle\Attribute\Anonymizer;
use WebEtDesign\UserBundle\Attribute\Exportable;

trait AddressFields
{
    #[Exportable(name: 'address')]
    #[Anonymizer(type: Anonymizer::TYPE_STRING)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $address = null;

    #[Exportable(name: 'address2')]
    #[Anonymizer(type: Anonymizer::TYPE_STRING)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $address2 = null;

    #[Exportable(name: 'city')]
    #[Anonymizer(type: Anonymizer::TYPE_STRING)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $city = null;

    #[Exportable(name: 'zipcode')]
    #[Anonymizer(type: Anonymizer::TYPE_STRING)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $zipCode = null;

    #[Exportable(name: 'country')]
    #[Anonymizer(type: Anonymizer::TYPE_STRING)]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $country = null;

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $address
     * @return AddressFields
     */
    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    /**
     * @param string|null $address2
     * @return AddressFields
     */
    public function setAddress2(?string $address2): self
    {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string|null $city
     * @return AddressFields
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string|null $zipCode
     * @return AddressFields
     */
    public function setZipCode(?string $zipCode): self
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     * @return AddressFields
     */
    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }
}
