<?php

namespace WebEtDesign\UserBundle\Command\User;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'user:create',
    description: 'Create new User',
)]
class UserCreateCommand extends Command
{
    public function __construct(
        protected UserPasswordHasherInterface $passwordHasher,
        protected EntityManagerInterface $em,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'email')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'username')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io            = new SymfonyStyle($input, $output);
        $email         = $input->hasOption('email') ? $input->getOption('email') : null;
        $username      = $input->hasOption('username') ? $input->getOption('username') : null;
        $plainPassword = $input->hasOption('password') ? $input->getOption('password') : null;

        if (empty($email)) {
            $email = $io->ask('Email');
        }

        if (empty($username)) {
            $username = $io->ask('Username');
        }

        if (empty($plainPassword)) {
            $plainPassword = $io->ask('Password');
        }

        $user     = new User();
        $password = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setEnabled(true);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setNewsletter(false);

        $this->em->persist($user);
        $this->em->flush();

        $io->success($user . ' has been created');

        return Command::SUCCESS;
    }
}
