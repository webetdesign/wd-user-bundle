<?php

namespace WebEtDesign\UserBundle\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\UserBundle\Repository\LoginAttemptRepository;

#[ORM\Entity(repositoryClass: LoginAttemptRepository::class)]
#[ORM\Table(name: "user__login_attempt")]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $ipAddress;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $date;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    private ?string $username;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true)]
    private ?string $firewall;

    public function __construct(?string $ipAddress = null, ?string $username = null, ?string $firewall = null)
    {
        $this->ipAddress = $ipAddress;
        $this->username = $username;
        $this->firewall = $firewall;
        $this->date = new DateTime('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getFirewall(): ?string
    {
        return $this->firewall;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setFirewall(?string $firewall): self
    {
        $this->firewall = $firewall;

        return $this;
    }
}
