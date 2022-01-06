<?php

namespace WebEtDesign\UserBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

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
    public function setAzureId(?string $azureId): AzureField
    {
        $this->azureId = $azureId;
        return $this;
    }
}
