<?php

namespace WebEtDesign\UserBundle\Command\Rgpd;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Event\RoutineInactivityNotification;
use WebEtDesign\UserBundle\Services\Anonymizer\AnonymizerInterface;

#[AsCommand(
    name: 'rgpd:inactive-user',
    description: 'Notifies users if they have not logged in for a while. Anonymizes users who have not logged in after being notified.',
)]
class RgpdInactiveUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $parameterBag,
        private EventDispatcherInterface $eventDispatcher,
        private RouterInterface $router,
        private AnonymizerInterface $anonymizer,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now               = new DateTime('now');
        $inactivityDate    = new DateTime('now -' . $this->parameterBag->get('wd_user.inactivity.duration'));
        $anonymizationDate = new DateTime('now -' . $this->parameterBag->get('wd_user.inactivity.duration_before_anonymization'));

        $userClass = $this->parameterBag->get('wd_user.user.class');

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
            ->from($userClass, 'u')
            ->andWhere('u.enabled = true')
            ->andWhere('u.lastLogin < :inactivityDate')
            ->andWhere($qb->expr()->isNull('u.notifyInactivityAt'))
            ->setParameters(['inactivityDate' => $inactivityDate]);

        $users  = $qb->getQuery()->getResult();
        $method = $this->parameterBag->get('wd_user.inactivity.callback');
        $notified = 0;

        foreach ($users as $user) {
            if (($method && method_exists($user, $method) && $user->$method()) || (!$method) || !method_exists($user, $method)) {
                $notified ++;
                $user->setNotifyInactivityAt($now);
                $this->em->persist($user);

                $event = new RoutineInactivityNotification($user);
                try {
                    $ctoLink = $this->router->generate(
                        $this->parameterBag->get('wd_user.inactivity.email_cto_route'), [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                    $event->setCtoLink($ctoLink);
                } catch (RouteNotFoundException $exception) {
                }

                $this->eventDispatcher->dispatch($event, RoutineInactivityNotification::NAME);
            }
        }

        $this->em->flush();

        $io->success($notified . ' ' . ngettext('user', 'users', $notified)
            . ' notified !');

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
            ->from($userClass, 'u')
            ->andWhere('u.enabled = true')
            ->andWhere('u.lastLogin < :inactivityDate')
            ->andWhere('u.notifyInactivityAt < :anonymizationDate')
            ->setParameters([
                'inactivityDate'    => $inactivityDate,
                'anonymizationDate' => $anonymizationDate
            ]);

        $users = $qb->getQuery()->getResult();

        foreach ($users as $user) {
            $this->anonymizer->anonimize($user);
            $this->em->persist($user);
        }

        $this->em->flush();

        $io->success(count($users) . ' ' . ngettext('user', 'users', count($users))
            . ' anonymize !');

        return Command::SUCCESS;
    }
}
