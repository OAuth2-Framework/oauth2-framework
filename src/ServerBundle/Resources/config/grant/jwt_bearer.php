<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(JwtBearerGrantType::class)
        ->args([
            service('jose.jws_verifier.oauth2_server.grant.jwt_bearer'),
            service('jose.header_checker.oauth2_server.grant.jwt_bearer'),
            service('jose.claim_checker.oauth2_server.grant.jwt_bearer'),
            service(ClientRepository::class),
            service(UserAccountRepository::class)->nullOnInvalid(),
        ])
    ;
};
