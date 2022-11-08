<?php

namespace WebEtDesign\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use WebEtDesign\RgpdBundle\Annotations\Anonymizer;
use WebEtDesign\RgpdBundle\Annotations\Exportable;

trait AzureField
{
    /**
     * @var ?string
     *
     * @Anonymizer(type=Anonymizer::TYPE_UNIQ)
     *
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\NotBlank(groups={"registration", "editProfile"})
     * @Exportable()
     */
    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Assert\NotBlank(groups: ["registration", "editProfile"])]
    protected ?string $azureId = null;

    /**
     * @return string|null
     */
    public function getAzureId(): ?string
    {
        return $this->azureId;
    }

    /**
     * @param string|null $azureId
     * @return AzureField
     */
    public function setAzureId(?string $azureId): self
    {
        $this->azureId = $azureId;
        return $this;
    }
}
