<?php

namespace WebEtDesign\UserBundle\Command\Rgpd;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Event\RoutineChangeOldPasswordReminder;

#[AsCommand(
    name: 'rgpd:reminder-old-password',
    description: 'Notifies the user if their password has not been updated for some time.',
)]
class RgpdReminderOldPasswordCommand extends Command
{
    /**
     * @inheritDoc
     */
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $eventDispatcher,
        private RouterInterface $router,
        private ParameterBagInterface $parameterBag,
        string $name = null
    ) {
        parent::__construct($name);
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);

        $now          = new DateTime('now');
        $validityDate = new DateTime('now -' . $this->parameterBag->get('wd_user.old_password_reminder.password_validity_duration_before_notify'));
        $notifyDate   = new DateTime('now -' . $this->parameterBag->get('wd_user.old_password_reminder.duration_between_notify'));

        $userClass = $this->parameterBag->get('wd_user.user.class');

        $qb = $this->em->createQueryBuilder();
        $qb->select('u')
            ->from($userClass, 'u')
            ->andWhere('u.enabled = true')
            ->andWhere('u.lastUpdatePassword < :validityDate')
            ->andWhere($qb->expr()->isNull('u.azureId'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->lt('u.notifyUpdatePasswordAt', ':notifyDate'),
                $qb->expr()->isNull('u.notifyUpdatePasswordAt')
            ))
            ->setParameters(['validityDate' => $validityDate, 'notifyDate' => $notifyDate]);


        $users = $qb->getQuery()->getResult();

        foreach ($users as $user) {
            $diff = date_diff($now, $user->getLastUpdatePassword());

            $useTime =
                ($diff->y > 0 ? $diff->y . ' ' . ngettext('an', 'ans', $diff->y) . ' ' : '') .
                ($diff->m > 0 ? $diff->m . ' mois ' : '') . 'et ' .
                ($diff->d > 0 ? $diff->d . ' ' . ngettext('jour', 'jours', $diff->d) : '');

            $user->setNotifyUpdatePasswordAt($now);
            $this->em->persist($user);

            $event = new RoutineChangeOldPasswordReminder($user);
            $event->setUseTime($useTime);
            try {
                $reset_link = $this->router->generate(
                    $this->parameterBag->get('wd_user.old_password_reminder.reset_password_route'), [], UrlGeneratorInterface::ABSOLUTE_URL
                );
                $event->setResetLink($reset_link);

            } catch (RouteNotFoundException $exception) {
            }

            $this->eventDispatcher->dispatch($event, RoutineChangeOldPasswordReminder::NAME);
        }

        $this->em->flush();

        $io->success(count($users) . ' ' . ngettext('user', 'users', count($users))
            . ' notified !');

        return Command::SUCCESS;
    }
}
