<?php


namespace WebEtDesign\UserBundle\Entity;


use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use Serializable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use WebEtDesign\RgpdBundle\Annotations\Anonymizable;
use WebEtDesign\RgpdBundle\Annotations\Anonymizer;
use WebEtDesign\RgpdBundle\Annotations\Exportable;
use WebEtDesign\RgpdBundle\Entity\RgpdUserFields;
use WebEtDesign\RgpdBundle\Validator\Constraints\PasswordStrength;

/**
 * @ORM\MappedSuperclass()
 * @Anonymizable()
 * @Exportable()
 */
#[ORM\MappedSuperclass]
abstract class WDUser implements UserInterface, Serializable, JsonSerializable, PasswordAuthenticatedUserInterface
{
    use IdentityFields;
    use RgpdUserFields;
    use TimestampableEntity;
    use AzureField;


    /**
     * @var null|int $id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")*
     *
     * @Exportable()
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * @var ?string
     *
     * @Anonymizer(type=Anonymizer::TYPE_UNIQ)
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"registration_username", "editProfile_username"})
     * @Exportable()
     */
    #[ORM\Column(type: Types::STRING, unique: true)]
    #[Assert\NotBlank(groups: ["registration", "editProfile"])]
    protected ?string $username = null;


    /**
     * @var ?string
     * @Assert\NotBlank(groups={"registration", "editProfile"})
     * @Assert\Email (groups={"registration", "editProfile"})
     * @ORM\Column(type="string", length=180, unique=true)
     * @Anonymizer(type=Anonymizer::TYPE_EMAIL)
     * @Exportable()
     */
    #[Assert\NotBlank(groups: ["registration", "editProfile"])]
    #[Assert\Email(groups: ["registration", "editProfile"])]
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    protected ?string $email = null;

    /**
     * @var null|string The hashed password
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $password = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $permissions = [];

    /**
     * @var null|string
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $token = null;

    /**
     * @var null|string
     * @ORM\Column(type="string", nullable=true)
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $confirmationToken = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $passwordRequestedAt = null;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?DateTime $lastLogin = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Anonymizer(type=Anonymizer::TYPE_BOOL_FALSE)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => false])]
    protected bool $enabled = false;

    /**
     * @Anonymizer(type=Anonymizer::TYPE_BOOL_FALSE)
     * @ORM\Column(type="boolean", nullable=true)
     */
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    protected ?bool $newsletter;

    /**
     * @Anonymizer(type=Anonymizer::TYPE_DATE)
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?DateTime $newsletterAcceptedAt;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=0})
     */
    #[ORM\Column(type: Types::BOOLEAN, nullable: false, options: ["default" => 0])]
    protected bool $isBanned = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $banReason = null;


    public function __toString()
    {
        return (string)$this->email;
    }

    /**
     * Get id.
     *
     * @return int $id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string)$this->email;
    }

    public function getUsernameForm(): string
    {
        return (string)$this->username;
    }

    /**
     * @param string|null $username
     * @return WDUser
     */
    public function setUsernameForm(?string $username): WDUser
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param string|null $username
     * @return WDUser
     */
    public function setUsername(?string $username): WDUser
    {
        $this->username = $username;

        return $this;
    }

    public function hasPermission($permission): bool
    {
        return in_array(strtoupper($permission), $this->getPermissions(), true);
    }

    public function addPermission($permissions): WDUser
    {
        $permissions = strtoupper($permissions);
        if (!in_array($permissions, $this->permissions, true)) {
            $this->permissions[] = $permissions;
        }

        return $this;
    }

    public function getPermissions(): array
    {
        $permissions = (array)$this->permissions;

        return array_unique($permissions);
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function hasRole($role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->getPermissions();

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     * @return WDUser
     */
    public function setLastLogin(?DateTime $lastLogin): WDUser
    {
        $this->setNotifyInactivityAt(null);

        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNewsletter(): ?bool
    {
        return $this->newsletter;
    }

    /**
     * @param bool|null $newsletter
     */
    public function setNewsletter(?bool $newsletter): self
    {
        $this->newsletter = $newsletter;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBanReason(): ?string
    {
        return $this->banReason;
    }

    /**
     * @param string|null $banReason
     */
    public function setBanReason(?string $banReason): void
    {
        $this->banReason = $banReason;
    }

    /**
     * @return bool
     */
    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    /**
     * @param bool $isBanned
     */
    public function setIsBanned(bool $isBanned): void
    {
        $this->isBanned = $isBanned;
    }

    /**
     * @param string|null $token
     * @return WDUser
     */
    public function setToken(?string $token): WDUser
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string $plainPassword
     * @return WDUser
     */
    public function setPlainPassword(?string $plainPassword): WDUser
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string|null
     * @PasswordStrength(minStrength=3, groups={"registration", "editProfile"})
     */

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $confirmationToken
     * @return WDUser
     */
    public function setConfirmationToken(?string $confirmationToken): WDUser
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    /**
     * @param DateTime|null $passwordRequestedAt
     * @return WDUser
     */
    public function setPasswordRequestedAt(?DateTime $passwordRequestedAt): WDUser
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPasswordRequestedAt(): ?DateTime
    {
        return $this->passwordRequestedAt;
    }

    /**
     * @param string|null $email
     * @return WDUser
     */
    public function setEmail(?string $email): WDUser
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return WDUser
     */
    public function setEnabled(bool $enabled): WDUser
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param DateTime|null $newsletterAcceptedAt
     * @return WDUser
     */
    public function setNewsletterAcceptedAt(?DateTime $newsletterAcceptedAt): WDUser
    {
        $this->newsletterAcceptedAt = $newsletterAcceptedAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getNewsletterAcceptedAt(): ?DateTime
    {
        return $this->newsletterAcceptedAt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'        => $this->getId(),
            'firstname' => $this->getFirstname(),
            'lastname'  => $this->getLastname(),
            'email'     => $this->getEmail(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): ?string
    {
        return serialize(
            [
                $this->password,
                $this->enabled,
                $this->id,
                $this->email,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        [
            $this->password,
            $this->enabled,
            $this->id,
            $this->email,
        ] = $data;
    }
}
