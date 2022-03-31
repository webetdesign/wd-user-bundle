<?php

namespace WebEtDesign\UserBundle\Command;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCreateCommand extends Command
{
    protected static $defaultName = 'user:create-admin';

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * UserCreateCommand constructor.
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager   = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Create admin')
            ->setDefinition([
                new InputArgument('email', InputArgument::REQUIRED, 'email'),
                new InputArgument('password', InputArgument::REQUIRED, 'password'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $email   = $input->getArgument('email');
        $password   = $input->getArgument('password');

        $user = new User();
        $user
            ->setUsername($email)
            ->setEmail($email)
            ->setPermissions(['ROLE_ADMIN'])
            ->setPassword($this->passwordEncoder->encodePassword($user, $password))
            ->setEnabled(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $io->success(sprintf('Created user %s', $email));
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
            $question = $io->ask('Veuillez choisir un email : ');
            $questions['email'] = $question;
        }

        if (!$input->getArgument('password')) {
            $question = $io->askHidden('Veuillez choisir un mot de passe : ');
            $questions['password'] = $question;
        }

        foreach ($questions as $name => $answer) {
            $input->setArgument($name, $answer);
        }
    }
}
