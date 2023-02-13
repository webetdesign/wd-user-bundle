<?php

namespace WebEtDesign\UserBundle;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

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

        $bundles = $builder->getParameter('kernel.bundles');
        if (isset($bundles['SonataAdminBundle'])) {
            $container->import('../config/sonata_admin.yaml');
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
            ->arrayNode('user')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('class')->defaultValue('App\Entity\User\User')->end()
                    ->scalarNode('login_path')->defaultValue('/login')->end()
                    ->scalarNode('logout_path')->defaultValue('/logout')->end()
                ->end()
            ->end()
            ->arrayNode('group')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('class')->defaultValue('App\Entity\User\Group')->end()
                ->end()
            ->end()
            ->arrayNode('login')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('success_redirect_route')->defaultValue('home')->end()
                ->end()
            ->end()
            ->arrayNode('resetting')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('success_redirect_route')->defaultValue('home')->end()
                ->end()
            ->end()
            ->arrayNode('impersonate')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('logout_route')->defaultValue('admin_app_user_user_list')->end()
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
