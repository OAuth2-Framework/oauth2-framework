<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2SecurityFactory implements SecurityFactoryInterface, AuthenticatorFactoryInterface
{
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): array|string {
        return $this->createProvider($container, $firewallName, $config);
    }

    /**
     * @param string      $id
     * @param array       $config
     * @param string      $userProviderId
     * @param string|null $defaultEntryPointId
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId): array
    {
        return [];
    }

    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
            ->scalarNode('user_provider')
            ->defaultNull()
            ->end()
            ->scalarNode('access_token_repository')
            ->defaultValue(AccessTokenRepository::class)
            ->end()
            ->scalarNode('token_type_manager')
            ->defaultValue(TokenTypeManager::class)
            ->end()
            ->scalarNode('failure_handler')
            ->defaultValue(DefaultFailureHandler::class)
            ->end()
            ->end()
        ;
    }

    private function createProvider(ContainerBuilder $container, string $id, array $config): string
    {
        $providerId = 'oauth2.security.authentication.provider.' . $id;
        $provider = new Definition(OAuth2Provider::class);
        $provider->setArgument(0, $config['user_provider'] !== null ? new Reference($config['user_provider']) : null);
        $provider->setArgument(1, new Reference($config['token_type_manager']));
        $provider->setArgument(2, new Reference($config['access_token_repository']));
        $provider->setArgument(3, new Reference($config['failure_handler']));
        $container->setDefinition($providerId, $provider);

        return $providerId;
    }
}
