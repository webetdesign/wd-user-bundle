<?php


namespace WebEtDesign\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Self_;
use WebEtDesign\RgpdBundle\Annotations\Anonymizer;
use WebEtDesign\RgpdBundle\Annotations\Exportable;

trait IdentityFields
{
    /**
     * @var ?string
     *
     * @Anonymizer()
     * @Exportable(name="prenom")
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $firstname = null;

    /**
     * @var ?string
     *
     * @Anonymizer()
     * @Exportable(name="nom")
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $lastname = null;

    /**
     * @var ?string
     *
     * @Anonymizer()
     * @Exportable(name="gender")
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $gender = null;

    /**
     * @var ?string
     *
     * @ORM\Column(type="string", nullable=true)
     */
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
