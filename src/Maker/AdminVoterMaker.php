<?php

namespace WebEtDesign\UserBundle\Maker;

use RuntimeException;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;
use WebEtDesign\UserBundle\Voter\AbstractAdminVoter;

class AdminVoterMaker extends AbstractMaker
{

    private string $skeletonDirectory;

    private string $abstractVoter;

    private string $projectDir;

    private string $modelClass = '';

    private string $modelClassBasename = '';

    private string $voterClassBasename = '';

    private string $roleName = '';

    private bool $generateVoter = true;

    private bool $generateRoles = true;

    public static function getCommandName(): string
    {
        return 'make:admin:voter';
    }

    public static function getCommandDescription(): string
    {
        return 'Generates an admin voter class based on the given model class';
    }

    public function __construct(KernelInterface $kernel)
    {
        $this->skeletonDirectory = sprintf('%s', __DIR__);
        $this->abstractVoter     = AbstractAdminVoter::class;
        $this->projectDir        = $kernel->getProjectDir();
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entity', InputArgument::OPTIONAL, 'The fully qualified entity class')
            ->addOption('voter', 'c', InputOption::VALUE_OPTIONAL, 'The directory')
            ->addOption('generateVoter', 'g', InputOption::VALUE_NONE, 'Generate the voter class')
            ->addOption('generateRole', 'r', InputOption::VALUE_NONE, 'Generate the role config');

        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->configure($input);
        if ($this->generateVoter) {
            $this->modelClass         = $io->ask(
                'The fully qualified entity class',
                $input->getArgument('entity')
            );
            $this->modelClassBasename = current(array_slice(explode('\\', $this->modelClass), -1));

            $this->voterClassBasename = $io->ask(
                'The voter class basename',
                $input->getOption('voter') ?: sprintf('%sVoter', $this->modelClassBasename)
            );
            $this->voterClassBasename = str_replace('Voter', '', $this->voterClassBasename);
        }

        $roleBasename   = strtoupper(str_replace('\\', '_', $this->voterClassBasename));
        $this->roleName = $io->ask(
            'The role name',
            $roleBasename ? sprintf('ROLE_%s_ADMIN', $roleBasename) : null
        );
        $this->roleName = 'ROLE_' . str_replace(['ROLE_', '_ADMIN'], '', $this->roleName) . '_ADMIN';
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $this->configure($input);

        if ($this->generateVoter) {
            $voterClassNameDetails = $generator->createClassNameDetails(
                $this->voterClassBasename,
                'Security\\Voter\\Admin\\',
                'Voter'
            );

            $this->generateVoter($io, $generator, $voterClassNameDetails);
        }

        if ($this->generateRoles) {
            $this->generateRoles($io, $this->roleName);
        }
    }

    private function configure(InputInterface $input): void
    {
        $generateVoter = $input->getOption('generateVoter');
        $generateRoles = $input->getOption('generateRole');

        $this->generateVoter = $generateVoter || !$generateRoles;
        $this->generateRoles = $generateRoles || !$generateVoter;
    }

    private function generateVoter(ConsoleStyle $io, Generator $generator, ClassNameDetails $voterClassNameDetails): void
    {
        $voterClassFullName = $voterClassNameDetails->getFullName();
        $generator->generateClass(
            $voterClassFullName,
            sprintf('%s/Voter.tpl.php', $this->skeletonDirectory),
            [
                'abstract_voter_namespace'  => $this->abstractVoter,
                'abstract_voter_short_name' => Str::getShortClassName($this->abstractVoter),
                'entity_namespace'          => $this->modelClass,
                'voter_name'                => $this->roleName,
                'entity_short_name'         => $this->modelClassBasename,
            ]
        );
        $generator->writeChanges();

        $io->writeln(sprintf(
            '%sThe voter class "<info>%s</info>" has been generated under the file "<info>%s</info>".',
            \PHP_EOL,
            $voterClassNameDetails->getShortName(),
            $voterClassFullName
        ));
    }

    private function generateRoles(ConsoleStyle $io, $roleName): void
    {
        $file = sprintf('%s/config/packages/%s', $this->projectDir, 'security_roles.yaml');

        $code = "security:\n   role_hierarchy:\n";
        if (is_file($file)) {
            $code = rtrim(file_get_contents($file));
            $data = (array)Yaml::parse($code);

            if ('' !== $code) {
                $code .= "\n";
            }

            if (array_key_exists('security', $data) && array_key_exists('role_hierarchy', (array)$data['security'])) {
                if (array_key_exists($roleName, (array)$data['security']['role_hierarchy'])) {
                    throw new RuntimeException(sprintf(
                        'The service "%s" is already defined in the file "%s".',
                        $roleName,
                        realpath($file)
                    ));
                }

                if (null !== $data['security']) {
                    $code .= "\n";
                }
            } else {
                $code .= '' === $code ? '' : "\n" . "security:\n   role_hierarchy:\n";
            }
        }

        $code .= "    {$roleName}:\n";
        $code .= "      - {$roleName}_READER\n";
        $code .= "      - {$roleName}_CREATE\n";
        $code .= "      - {$roleName}_EDIT\n";
        $code .= "      - {$roleName}_DELETE\n\n";
        $code .= "    {$roleName}_READER:\n";
        $code .= "      - {$roleName}_LIST\n";
        $code .= "      - {$roleName}_VIEW\n";
        $code .= "      - {$roleName}_EXPORT\n";

        if (false === @file_put_contents($file, $code)) {
            throw new RuntimeException(sprintf(
                'Unable to append role "%s" to the file "%s". You will have to do it manually.',
                $roleName,
                $file
            ));
        }
        $io->writeln(sprintf(
            '%sThe roles "<info>%s</info>" has been generated under the file "<info>%s</info>".',
            \PHP_EOL,
            $roleName,
            $file
        ));
    }
}
