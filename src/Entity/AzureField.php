<?php

namespace WebEtDesign\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\UserBundle\Attribute\Anonymizer;
use WebEtDesign\UserBundle\Attribute\Exportable;

trait AzureField
{
    #[Exportable]
    #[Anonymizer(type: Anonymizer::TYPE_UNIQ)]
    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    protected ?string $azureId = null;

    public function getAzureId(): ?string
    {
        return $this->azureId;
    }

    public function setAzureId(?string $azureId): self
    {
        $this->azureId = $azureId;
        return $this;
    }
}
