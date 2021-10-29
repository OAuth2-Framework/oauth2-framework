<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethod;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler\ClientAuthenticationMethodCompilerPass;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientAuthenticationSource implements Component
{
    /**
     * @var Component[]
     */
    private array $subComponents = [];

    public function __construct()
    {
        $this->subComponents = [
            new NoneSource(),
            new ClientSecretBasicSource(),
            new ClientSecretPostSource(),
            new ClientAssertionJwtSource(),
        ];
    }

    public function name(): string
    {
        return 'client_authentication';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (! class_exists(AuthenticationMethodManager::class)) {
            return;
        }

        $container->registerForAutoconfiguration(AuthenticationMethod::class)->addTag(
            'oauth2_server_client_authentication'
        );
        $loader = new PhpFileLoader($container, new FileLocator(
            __DIR__ . '/../../Resources/config/client_authentication'
        ));
        $loader->load('client_authentication.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (! class_exists(AuthenticationMethodManager::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
        ;

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (! class_exists(AuthenticationMethodManager::class)) {
            return [];
        }
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge($updatedConfig, $subComponent->prepend($container, $config));
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container): void
    {
        if (! class_exists(AuthenticationMethodManager::class)) {
            return;
        }
        $container->addCompilerPass(new ClientAuthenticationMethodCompilerPass());
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
