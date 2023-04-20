<?php


namespace WebEtDesign\UserBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait RgpdAnonymizeFields
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $anonymizedAt = null;

    public function getAnonymizedAt(): ?DateTimeInterface
    {
        return $this->anonymizedAt;
    }

    public function setAnonymizedAt(?DateTimeInterface $anonymizedAt): void
    {
        $this->anonymizedAt = $anonymizedAt;
    }


    public function isAnonyme(){
        return $this->getAnonymizedAt() !== null;
    }
}
