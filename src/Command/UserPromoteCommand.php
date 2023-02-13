<?php

namespace WebEtDesign\UserBundle\Command;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'user:promote',
    description: 'Add role to user',
)]
class UserPromoteCommand extends Command
{
    public function __construct(
        protected EntityManagerInterface $em,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, 'User identifier (email, username)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $identifier = $input->hasOption('identifier') ? $input->getOption('identifier') : null;

        if (empty($identifier)) {
            $identifier = $io->ask('Identifier', null, function ($answer) {
                if (empty($answer)) {
                    return throw new \RuntimeException('Identifier can not be empty.');
                }
                return $answer;
            });
        }

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->loadUserByIdentifier($identifier);

        if (null === $user) {
            $io->error('User not found');
            return Command::FAILURE;
        }

        while (!empty($role = $io->ask('Role <fg=yellow>[Empty value to exit]</>'))) {
            $user->addPermission($role);
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success("User $user has been updated");

        return Command::SUCCESS;
    }
}
