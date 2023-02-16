<?php


namespace WebEtDesign\UserBundle\Command\Rgpd;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\UserBundle\Repository\LoginAttemptRepository;

#[AsCommand(
    name: 'rgpd:clean-login-attempts',
    description: 'Clean login attempts',
)]
class CleanLoginAttempts extends Command
{
    public function __construct(
        private ParameterBagInterface $params,
        private LoginAttemptRepository $loginAttemptRepository,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delay = $this->params->get('wd_user.security.admin_delay');

        $this->loginAttemptRepository->deleteOldLoginAttempts($delay);

        return 0;
    }
}
