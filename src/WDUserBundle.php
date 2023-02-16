<?php

namespace WebEtDesign\UserBundle;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WebEtDesign\UserBundle\Attribute\Anonymizer;
use WebEtDesign\UserBundle\Attribute\Exportable;

/**
 * Class WebEtDesignWDUserBundle
 * @package WebEtDesign\WDUserBundle
 */
class WDUserBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $container->parameters()->set(
            'wd_user.user.class',
            $config['user']['class']
        );
        $container->parameters()->set(
            'wd_user.group.class',
            $config['group']['class']
        );
        $container->parameters()->set(
            'wd_user.user.login_path',
            $config['user']['login_path']
        );
        $container->parameters()->set(
            'wd_user.user.logout_path',
            $config['user']['logout_path']
        );
        $container->parameters()->set(
            'wd_user.login.success_redirect_route',
            $config['login']['success_redirect_route']
        );
        $container->parameters()->set(
            'wd_user.resetting.success_redirect_route',
            $config['resetting']['success_redirect_route']
        );
        $container->parameters()->set(
            'wd_user.azure.clients',
            $config['azure_directory']['clients']
        );
        $container->parameters()->set(
            'wd_user.impersonate.logout_route',
            $config['impersonate']['logout_route']
        );
        $container->parameters()->set(
            'wd_user.export.zip_private_path',
            $config['export']['zip_private_path']
        );
        $container->parameters()->set(
            'wd_user.old_password_reminder.password_validity_duration_before_notify',
            $config['old_password_reminder']['password_validity_duration_before_notify']
        );
        $container->parameters()->set(
            'wd_user.old_password_reminder.duration_between_notify',
            $config['old_password_reminder']['duration_between_notify']
        );
        $container->parameters()->set(
            'wd_user.old_password_reminder.reset_password_route',
            $config['old_password_reminder']['reset_password_route']
        );
        $container->parameters()->set(
            'wd_user.security.delay',
            $config['security']['delay']
        );
        $container->parameters()->set(
            'wd_user.security.max_attempts',
            $config['security']['max_attempts']
        );
        $container->parameters()->set(
            'wd_user.inactivity.duration',
            $config['inactivity']['duration']
        );
        $container->parameters()->set(
            'wd_user.inactivity.duration_before_anonymization',
            $config['inactivity']['duration_before_anonymization']
        );
        $container->parameters()->set(
            'wd_user.inactivity.email_cto_route',
            $config['inactivity']['email_cto_route']
        );
        $container->parameters()->set(
            'wd_user.inactivity.callback',
            $config['inactivity']['callback']
        );

        $bundles = $builder->getParameter('kernel.bundles');

        if (isset($bundles['SonataAdminBundle'])) {
            $container->import('../config/sonata_admin.yaml');
        }

        $exporterService = $builder->getDefinition('WebEtDesign\UserBundle\Services\Exporter\Exporter');
        $anonymizerService = $builder->getDefinition('WebEtDesign\UserBundle\Services\Anonymizer\Anonymizer');

        if (isset($bundles['VichUploaderBundle'])) {
            $container->import('../config/vich_services.yaml');
            $exporterVichService = $builder->getDefinition('WebEtDesign\UserBundle\Exporter\ExporterVich');
            $exporterService->addMethodCall('addExporter', [$exporterVichService, Exportable::TYPE_VICH_UPLOADER]);
            $anonymizerVichService = $builder->getDefinition('WebEtDesign\UserBundle\Anonymizer\AnonymizerVich');
            $anonymizerService->addMethodCall('addAnonymizer', [$anonymizerVichService, Anonymizer::TYPE_VICH]);
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
            ->arrayNode('user')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('class')->defaultValue('App\Entity\User\User')->end()
                    ->scalarNode('login_path')->defaultValue('/login')->end()
                    ->scalarNode('logout_path')->defaultValue('/logout')->end()
                ->end()
            ->end()
            ->arrayNode('group')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('class')->defaultValue('App\Entity\User\Group')->end()
                ->end()
            ->end()
            ->arrayNode('login')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('success_redirect_route')->defaultValue('home')->end()
                ->end()
            ->end()
            ->arrayNode('resetting')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('success_redirect_route')->defaultValue('home')->end()
                ->end()
            ->end()
            ->arrayNode('impersonate')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('logout_route')->defaultValue('admin_app_user_user_list')->end()
                ->end()
            ->end()
            ->arrayNode('export')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('zip_private_path')->defaultValue('var/export_data')->end()
                ->end()
            ->end()
            ->arrayNode('old_password_reminder')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('password_validity_duration_before_notify')->defaultValue('12 month')->end()
                    ->scalarNode('duration_between_notify')->defaultValue('6 month')->end()
                    ->scalarNode('reset_password_route')->defaultValue('sonata_user_admin_resetting_request')->end()
                ->end()
            ->end()
            ->arrayNode('inactivity')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('duration')->defaultValue('12 month')->end()
                    ->scalarNode('duration_before_anonymization')->defaultValue('1 month')->end()
                    ->scalarNode('email_cto_route')->defaultNull()->end()
                    ->scalarNode('callback')->defaultNull()->end()
                ->end()
            ->end()
            ->arrayNode('security')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('delay')->defaultValue(15)->end()
                    ->scalarNode('max_attempts')->defaultValue(5)->end()
                ->end()
            ->end()
            ->append($this->addAzureConfig())
            //                ->arrayNode('azure_directory')
            //                    ->children()
            //                        ->arrayNode('clients')
            //                            ->arrayPrototype()
            //                                ->addDefaultsIfNotSet()
            //                                ->children()
            //                                ->scalarNode('client_name')->defaultValue(null)->end()
            //                                ->scalarNode('domain')->defaultValue(null)->end()
            //                                ->scalarNode('domain_regex')->defaultValue(null)->end()
            //                                ->arrayNode('roles')
            //                                    ->scalarPrototype()->defaultValue([])->end()
            //                                ->end()
            //                            ->end()
            //                        ->end()
            //                ->end()

        ->end();
    }

    private function addAzureConfig(): ArrayNodeDefinition|NodeDefinition
    {
        $tree = new TreeBuilder('azure_directory');
        $node = $tree->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('clients')
                ->defaultValue([])
                ->arrayPrototype()
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')->defaultTrue()->end()
                    ->scalarNode('client_name')->isRequired()->end()
                    //->scalarNode('domains')->isRequired()->end()
                    ->arrayNode('domains')
                    ->cannotBeEmpty()
                    ->scalarPrototype()->isRequired()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('roles')
                    ->scalarPrototype()->defaultValue([])->end()
                    ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
