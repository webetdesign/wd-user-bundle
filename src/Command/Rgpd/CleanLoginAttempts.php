<?php
declare(strict_types=1);


namespace WebEtDesign\UserBundle\Command\Rgpd;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\UserBundle\Repository\LoginAttemptRepository;

#[AsCommand(
    name: CleanLoginAttempts::NAME,
    description: 'Clean login attempts',
)]
class CleanLoginAttempts extends Command
{
    public const NAME = 'rgpd:clean-login-attempts';

    public function __construct(
        private ParameterBagInterface $params,
        private LoginAttemptRepository $loginAttemptRepository,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 'wd_user.security.admin_delay' n'a jamais été déclaré par la configuration du bundle :
        // la commande levait une ParameterNotFoundException à chaque exécution, et la table n'a
        // donc jamais pu être purgée.
        $delay = $this->params->get('wd_user.security.cleanup_delay');

        $deleted = $this->loginAttemptRepository->deleteOldLoginAttempts($delay);

        $output->writeln(sprintf('%d login attempt(s) deleted.', $deleted));

        return Command::SUCCESS;
    }
}
