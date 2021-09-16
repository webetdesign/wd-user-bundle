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
                    ->children()
                        ->scalarNode('class')->defaultValue('App\Entity\User\User')->end()
                    ->end()
                ->end()
                ->arrayNode('group')
                    ->children()
                        ->scalarNode('class')->defaultValue('App\Entity\User\Group')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
