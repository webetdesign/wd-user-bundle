<?php


namespace WebEtDesign\UserBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait RgpdAnonymizeFields
{
    /**
     * @var DateTimeInterface|null $anonymizedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $anonymizedAt = null;

    /**
     * @return DateTimeInterface|null
     */
    public function getAnonymizedAt(): ?DateTimeInterface
    {
        return $this->anonymizedAt;
    }

    /**
     * @param DateTimeInterface|null $anonymizedAt
     */
    public function setAnonymizedAt(?DateTimeInterface $anonymizedAt): void
    {
        $this->anonymizedAt = $anonymizedAt;
    }


    public function isAnonyme(){
        return $this->getAnonymizedAt() !== null;
    }
}
