<?php

namespace WebEtDesign\UserBundle\Command\Rgpd;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WebEtDesign\UserBundle\Entity\WDUser;
use WebEtDesign\UserBundle\Services\Exporter\ExporterInterface;

#[AsCommand(
    name: 'rgpd:user-export',
    description: 'Export RGPD data for one user.',
)]
class RgpdUserExportCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $parameterBag,
        private ExporterInterface $exporter,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('identifier', InputArgument::REQUIRED, 'User id, email or username');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = (string) $input->getArgument('identifier');

        $user = $this->resolveUser($identifier);
        if ($user === null) {
            $io->error(sprintf('User "%s" not found.', $identifier));

            return Command::FAILURE;
        }

        $json = $this->exporter->export($user);
        $data = json_decode($json, true);

        $io->success(sprintf('RGPD export generated for user #%d (%s).', $user->getId(), $user->getEmail()));

        if (\is_array($data) && !empty($data['_archive'])) {
            $io->writeln('Archive URL: ' . $data['_archive']);
        }

        $io->writeln($json);

        return Command::SUCCESS;
    }

    private function resolveUser(string $identifier): ?WDUser
    {
        $userClass = $this->parameterBag->get('wd_user.user.class');
        $repository = $this->em->getRepository($userClass);

        if (ctype_digit($identifier)) {
            $user = $repository->find((int) $identifier);
            if ($user instanceof WDUser) {
                return $user;
            }
        }

        if (method_exists($repository, 'loadUserByIdentifier')) {
            $user = $repository->loadUserByIdentifier($identifier);
            if ($user instanceof WDUser) {
                return $user;
            }
        }

        $user = $repository->findOneBy(['email' => $identifier]);
        if ($user instanceof WDUser) {
            return $user;
        }

        $user = $repository->findOneBy(['username' => $identifier]);

        return $user instanceof WDUser ? $user : null;
    }
}
