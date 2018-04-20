<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Firewall;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler;
use OAuth2Framework\ServerBundle\Annotation\Checker\Checker;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Security\Factory\OAuth2SecurityFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class FirewallSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'firewall';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['firewall'];
        $container->setParameter('oauth2_server.firewall.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');
        $container->registerForAutoconfiguration(AccessTokenHandler::class)->addTag('oauth2_access_token_handler');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/firewall'));
        $loader->load('security.php');
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        if (!$container->hasExtension('security')) {
            return;
        }
        /** @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new OAuth2SecurityFactory());

        $container->addCompilerPass(new SecurityAnnotationCheckerCompilerPass());
        $container->addCompilerPass(new AccessTokenHandlerCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
