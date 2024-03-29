<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class WDGroup
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    protected string $code = '';

    #[ORM\Column(type: Types::STRING)]
    protected string $name = '';

    #[ORM\Column(type: Types::JSON)]
    protected array $permissions = [];

    public function __construct(string $name, array $permissions = [])
    {
        $this->name        = $name;
        $this->permissions = $permissions;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function addPermission($role): WDGroup
    {
        if (!$this->hasPermission($role)) {
            $this->permissions[] = strtoupper($role);
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

    public function hasPermission($permission): bool
    {
        return in_array(strtoupper($permission), $this->permissions, true);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function removePermission($permission): WDGroup
    {
        if (false !== $key = array_search(strtoupper($permission), $this->permissions, true)) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
        }

        return $this;
    }

    public function setName($name): WDGroup
    {
        $this->name = $name;

        return $this;
    }

    public function setPermissions(array $permissions): WDGroup
    {
        $this->permissions = $permissions;

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
