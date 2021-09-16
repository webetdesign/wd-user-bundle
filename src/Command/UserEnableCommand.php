<?php

namespace WebEtDesign\UserBundle\Command;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserEnableCommand extends Command
{
    protected static $defaultName = 'user:activate';

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * UserCreateCommand constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager   = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Activate user')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'email'),
                new InputArgument('state', InputArgument::REQUIRED, 'state'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email   = $input->getArgument('email');
        $state   = $input->getArgument('state');

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if(!$user){
            $io->error('User ' . $email . " can't be found");
            return 0;
        }

        $user->setEnabled($state === 'yes');
        $this->entityManager->flush();

        $io->success($email . ($user->isEnabled() ? ' enabled' : ' disabled'));

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];
        $io = new SymfonyStyle($input, $output);

        $io->comment('Configuration');

        if (!$input->getArgument('email')) {
            $question = $io->ask('Veuillez saisir le email : ');
            $questions['email'] = $question;
        }

        if (!$input->getArgument('state')) {
            $question = $io->ask('Voulez vous activer cet utilisateur ? (yes/no)', 'yes', function($value){
                if($value !== 'yes' && $value !== 'no'){
                    throw new \RuntimeException("Response must be 'yes' or 'no'");
                }
                return $value;
            });
            $questions['state'] = $question;
        }

        foreach ($questions as $name => $answer) {
            $input->setArgument($name, $answer);
        }
    }
}
