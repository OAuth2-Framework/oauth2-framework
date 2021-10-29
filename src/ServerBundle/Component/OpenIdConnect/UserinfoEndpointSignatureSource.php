<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use function count;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler\UserinfoEndpointSignatureCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserinfoEndpointSignatureSource implements Component
{
    public function name(): string
    {
        return 'signature';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs['openid_connect']['userinfo_endpoint']['signature'];
        $container->setParameter(
            'oauth2_server.openid_connect.userinfo_endpoint.signature.enabled',
            $config['enabled']
        );
        if (! $config['enabled']) {
            return;
        }

        $container->setParameter(
            'oauth2_server.openid_connect.userinfo_endpoint.signature.signature_algorithms',
            $config['signature_algorithms']
        );
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && count($config['signature_algorithms']) === 0;
            })
            ->thenInvalid('You must set at least one signature algorithm.')
            ->end()
            ->children()
            ->arrayNode('signature_algorithms')
            ->info('Signature algorithm used to sign the user information.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike([])
            ->treatFalseLike([])
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new UserinfoEndpointSignatureCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        $sourceConfig = $config['openid_connect']['userinfo_endpoint'][$this->name()];

        ConfigurationHelper::addJWSBuilder(
            $container,
            'oauth2_server.openid_connect.id_token_from_userinfo',
            $sourceConfig['signature_algorithms'],
            false
        );

        return [];
    }
}
