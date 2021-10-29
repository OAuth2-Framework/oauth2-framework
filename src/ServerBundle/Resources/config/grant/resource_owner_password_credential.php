<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialManager;
use OAuth2Framework\Component\ResourceOwnerPasswordCredentialsGrant\ResourceOwnerPasswordCredentialsGrantType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ResourceOwnerPasswordCredentialsGrantType::class)
        ->args([
            service(ResourceOwnerPasswordCredentialManager::class),
            service(UserAccountRepository::class),
            service(ClientRepository::class),
        ])
    ;
};
