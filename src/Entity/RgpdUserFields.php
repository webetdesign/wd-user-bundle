<?php


namespace WebEtDesign\UserBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use WebEtDesign\UserBundle\Validator\Constraints as WDConstraints;


trait RgpdUserFields
{
    use RgpdAnonymizeFields;

    /**
     * @WDConstraints\PasswordStrength(minLength=6, minStrength=4, groups={"Registration", "Profile", "ResetPassword", "ChangePassword"})
     */
    protected $plainPassword; // TODO : Attribute validator

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(field: ["password"], on:"change")]
    #[Gedmo\Timestampable(on:"create")]
    protected ?DateTimeInterface $lastUpdatePassword = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $notifyUpdatePasswordAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $notifyInactivityAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $rgpdAcceptedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $anonymizedAt = null;

    public $rgpdConfirm;

    public function getLastUpdatePassword(): ?DateTimeInterface
    {
        return $this->lastUpdatePassword;
    }

    public function setLastUpdatePassword(DateTimeInterface $lastUpdatePassword): self
    {
        $this->lastUpdatePassword = $lastUpdatePassword;

        return $this;
    }

    public function getNotifyUpdatePasswordAt(): ?DateTime
    {
        return $this->notifyUpdatePasswordAt;
    }

    public function setNotifyUpdatePasswordAt(?DateTime $notifyUpdatePasswordAt): self
    {
        $this->notifyUpdatePasswordAt = $notifyUpdatePasswordAt;
        return $this;
    }

    public function getNotifyInactivityAt(): ?DateTime
    {
        return $this->notifyInactivityAt;
    }

    public function setNotifyInactivityAt(?DateTime $notifyInactivityAt): self
    {
        $this->notifyInactivityAt = $notifyInactivityAt;
        return $this;
    }

    public function getRgpdAcceptedAt(): ?\DateTimeInterface
    {
        return $this->rgpdAcceptedAt;
    }

    public function setRgpdAcceptedAt(?\DateTimeInterface $rgpdAcceptedAt): self
    {
        $this->rgpdAcceptedAt = $rgpdAcceptedAt;

        return $this;
    }

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
