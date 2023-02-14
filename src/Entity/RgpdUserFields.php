<?php


namespace WebEtDesign\UserBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


trait RgpdUserFields
{
    use RgpdAnonymizeFields;

    protected ?string $plainPassword = null;

    /**
     * @var ?DateTimeInterface $lastUpdatePassword
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     * @Gedmo\Timestampable(on="change", field={"password"})
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(field: ["password"])]
    protected ?DateTimeInterface $lastUpdatePassword = null;

    /**
     * @var ?DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $notifyUpdatePasswordAt;

    /**
     * @var ?DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $notifyInactivityAt;

    /**
     * @var DateTimeInterface|null $rgpdAcceptedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $rgpdAcceptedAt = null;

    /**
     * @var DateTimeInterface|null $anonymizedAt
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTimeInterface $anonymizedAt = null;

    public bool $rgpdConfirm = false;

    public function getLastUpdatePassword(): ?DateTimeInterface
    {
        return $this->lastUpdatePassword;
    }

    public function setLastUpdatePassword(DateTimeInterface $lastUpdatePassword): self
    {
        $this->lastUpdatePassword = $lastUpdatePassword;

        return $this;
    }

    /**
     * @return ?DateTime
     */
    public function getNotifyUpdatePasswordAt(): ?DateTime
    {
        return $this->notifyUpdatePasswordAt;
    }

    /**
     * @param ?DateTime $notifyUpdatePasswordAt
     * @return self
     */
    public function setNotifyUpdatePasswordAt(?DateTime $notifyUpdatePasswordAt): self
    {
        $this->notifyUpdatePasswordAt = $notifyUpdatePasswordAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getNotifyInactivityAt(): ?DateTime
    {
        return $this->notifyInactivityAt;
    }

    /**
     * @param DateTime|null $notifyInactivityAt
     * @return self
     */
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
}
