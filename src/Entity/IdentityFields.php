<?php


namespace WebEtDesign\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\UserBundle\Attribute\Anonymizer;
use WebEtDesign\UserBundle\Attribute\Exportable;

trait IdentityFields
{
    #[Exportable(name: 'prenom')]
    #[Anonymizer()]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $firstname = null;

    #[Exportable(name: 'nom')]
    #[Anonymizer()]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $lastname = null;

    #[Exportable(name: 'gender')]
    #[Anonymizer()]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $gender = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $locale = null;

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     * @return IdentityFields
     */
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     * @return IdentityFields
     */
    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param string|null $gender
     * @return IdentityFields
     */
    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     * @return IdentityFields
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }
}
