<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Grant\JwtBearer;

use function count;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler\EncryptedAssertionCompilerPass;
use OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler\TrustedIssuerSupportCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class JwtBearerSource implements Component
{
    public function name(): string
    {
        return 'jwt_bearer';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (! class_exists(JwtBearerGrantType::class) || ! $configs['grant']['jwt_bearer']['enabled']) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/grant'));
        $loader->load('jwt_bearer.php');

        $container->setParameter(
            'oauth2_server.grant.jwt_bearer.encryption.enabled',
            $configs['grant']['jwt_bearer']['encryption']['enabled']
        );
        if ($configs['grant']['jwt_bearer']['encryption']['enabled']) {
            $container->setParameter(
                'oauth2_server.grant.jwt_bearer.encryption.required',
                $configs['grant']['jwt_bearer']['encryption']['required']
            );
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (! class_exists(JwtBearerGrantType::class)) {
            return;
        }
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->validate()
            ->ifTrue(static function ($config): bool {
                return $config['enabled'] === true && count($config['signature_algorithms']) === 0;
            })
            ->thenInvalid('The option "signature_algorithms" must contain at least one signature algorithm.')
            ->end()
            ->children()
            ->arrayNode('signature_algorithms')
            ->info('Signature algorithms supported by this grant type.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike([])
            ->end()
            ->arrayNode('claim_checkers')
            ->info('Checkers will verify the JWT claims.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike(['exp', 'iat', 'nbf'])
            ->end()
            ->arrayNode('header_checkers')
            ->info('Checkers will verify the JWT headers.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike(['crit'])
            ->end()
            ->arrayNode('encryption')
            ->canBeEnabled()
            ->children()
            ->booleanNode('required')
            ->info('If set to true, all ID Token sent to the server must be encrypted.')
            ->defaultFalse()
            ->end()
            ->arrayNode('key_encryption_algorithms')
            ->info('Supported key encryption algorithms.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike([])
            ->end()
            ->scalarNode('key_set')
            ->info('The key set used to decrypt incoming assertions.')
            ->end()
            ->arrayNode('content_encryption_algorithms')
            ->info('Supported content encryption algorithms.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()
            ->end()
            ->treatNullLike([])
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        if (! class_exists(JwtBearerGrantType::class)) {
            return;
        }
        $container->addCompilerPass(new TrustedIssuerSupportCompilerPass());
        $container->addCompilerPass(new EncryptedAssertionCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $configs): array
    {
        if (! class_exists(JwtBearerGrantType::class) || ! $configs['grant']['jwt_bearer']['enabled']) {
            return [];
        }
        $this->updateJoseBundleConfigurationForVerifier($container, $configs['grant']['jwt_bearer']);
        $this->updateJoseBundleConfigurationForDecrypter($container, $configs['grant']['jwt_bearer']['encryption']);

        return [];
    }

    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, array $sourceConfig): void
    {
        ConfigurationHelper::addJWSVerifier(
            $container,
            'oauth2_server.grant.jwt_bearer',
            $sourceConfig['signature_algorithms'],
            false
        );
        ConfigurationHelper::addHeaderChecker(
            $container,
            'oauth2_server.grant.jwt_bearer',
            $sourceConfig['header_checkers'],
            false
        );
        ConfigurationHelper::addClaimChecker($container, 'oauth2_server.grant.jwt_bearer', [], false);
    }

    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, array $sourceConfig): void
    {
        if (! $sourceConfig['enabled']) {
            return;
        }
        ConfigurationHelper::addKeyset($container, 'oauth2_server.grant.jwt_bearer', 'jwkset', [
            'value' => $sourceConfig['key_set'],
        ], false);
        ConfigurationHelper::addJWEDecrypter(
            $container,
            'oauth2_server.grant.jwt_bearer',
            $sourceConfig['key_encryption_algorithms'],
            $sourceConfig['content_encryption_algorithms'],
            ['DEF'],
            false
        );
    }
}
