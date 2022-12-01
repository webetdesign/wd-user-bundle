<?php

namespace WebEtDesign\UserBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class WDUserExtension extends Extension
{
    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $config        = $processor->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container,
            new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setParameter(
            'wd_user.user.class',
            $config['user']['class']
        );

        $container->setParameter(
            'wd_user.group.class',
            $config['group']['class']
        );

        $container->setParameter(
            'wd_user.user.login_path',
            $config['user']['login_path']
        );

        $container->setParameter(
            'wd_user.user.logout_path',
            $config['user']['logout_path']
        );

        $container->setParameter(
            'wd_user.login.success_redirect_route',
            $config['login']['success_redirect_route']
        );

        $container->setParameter(
            'wd_user.resetting.success_redirect_route',
            $config['resetting']['success_redirect_route']
        );

        $container->setParameter(
            'wd_user.azure_connect.clients',
            $config['azure_directory']['clients']
        );

        $container->setParameter(
            'wd_user.impersonate.logout_route',
            $config['impersonate']['logout_route']
        );

        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['SonataAdminBundle'])) {
            $loader->load('sonata_admin.yaml');
        }
    }

    private function configAzure(array $config, ContainerBuilder $container){


        $container->setParameter(
            'wd_user.azure_connect.clients',
            $config['azure_directory']['clients']
        );
    }
}
