<?php


namespace WebEtDesign\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class WDGroup
{
    /**
     * @var null|int $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $code = '';

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected string $name = '';

    /**
     * @var array
     * @ORM\Column(type="json")
     */
    protected array $roles = [];

    /**
     * Group constructor.
     *
     * @param string $name
     * @param array $roles
     */
    public function __construct(string $name, array $roles = array())
    {
        $this->name = $name;
        $this->roles = $roles;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function addRole($role): WDGroup
    {
        if (!$this->hasRole($role)) {
            $this->roles[] = strtoupper($role);
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasRole($role): bool
    {
        return in_array(strtoupper($role), $this->roles, true);
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function removeRole($role): WDGroup
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

    public function setName($name): WDGroup
    {
        $this->name = $name;

        return $this;
    }

    public function setRoles(array $roles): WDGroup
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @param string $code
     * @return WDGroup
     */
    public function setCode(string $code): WDGroup
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
