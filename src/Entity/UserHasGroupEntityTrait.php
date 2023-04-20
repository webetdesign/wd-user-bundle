<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Entity;

use App\Entity\User\Group;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait UserHasGroupEntityTrait
{

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'users', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'user__user_has_group')]
    protected Collection|ArrayCollection $groups;

    public function getRoles(): array
    {
        $roles = $this->getPermissions();

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getPermissions());
        }

        return array_unique($roles);
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Group $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
        }

        return $this;
    }

    public function removeGroup(Group $group): self
    {
        $this->groups->removeElement($group);

        return $this;
    }

}
