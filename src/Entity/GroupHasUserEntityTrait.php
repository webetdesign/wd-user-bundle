<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Entity;

use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait GroupHasUserEntityTrait
{

        #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
        private Collection|ArrayCollection $users;

        /**
         * @return Collection<int, User>
         */
        public function getUsers(): Collection
        {
            return $this->users;
        }

        public function addUser(User $user): self
        {
            if (!$this->users->contains($user)) {
                $this->users[] = $user;
                $user->addGroup($this);
            }

            return $this;
        }

        public function removeUser(User $user): self
        {
            if ($this->users->removeElement($user)) {
                $user->removeGroup($this);
            }

            return $this;
        }

}
