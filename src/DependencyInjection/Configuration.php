<?php

namespace WebEtDesign\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wd_user');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
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
                ->arrayNode('azure_directory')
                    ->children()
                        ->arrayNode('clients')
                            ->arrayPrototype()
                                ->addDefaultsIfNotSet()
                                ->children()
                                ->scalarNode('client_name')->defaultValue(null)->end()
                                ->scalarNode('domain')->defaultValue(null)->end()
                                ->scalarNode('domain_regex')->defaultValue(null)->end()
                                ->arrayNode('roles')
                                    ->scalarPrototype()->defaultValue([])->end()
                                ->end()
                            ->end()
                        ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
