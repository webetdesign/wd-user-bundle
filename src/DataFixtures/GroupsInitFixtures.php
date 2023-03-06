<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\DataFixtures;

use App\Entity\User\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\UserBundle\Enum\GroupEnum;

class GroupsInitFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(protected ParameterBagInterface $parameterBag) { }

    public static function getGroups(): array
    {
        return ['init', 'groups'];
    }

    public function load(ObjectManager $manager): void
    {
        $groupEnum = $this->parameterBag->get('wd_user.group.enum');

        foreach ($groupEnum::cases() as $enum) {
            $group = $manager->getRepository(Group::class)->findOneBy(['code' => $enum->name]);
            if (!$group) {
                $group = new Group($enum->label());
                $group->setCode($enum->name);
            }

            $group->setPermissions($enum->roles());

            $manager->persist($group);
        }

        $manager->flush();
    }
}
