<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\TokenType\TokenTypeMiddleware;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtensionManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeMiddleware;
use OAuth2Framework\Component\TokenEndpoint\TokenEndpoint;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_pipe')
        ->class(Pipe::class)
        ->args([[
            service('oauth2_server.message_middleware.for_client_authentication'),
            service('oauth2_server.client_authentication.middleware'),
            service(GrantTypeMiddleware::class),
            service(TokenTypeMiddleware::class),
            service(TokenEndpoint::class),
        ]])
    ;

    $container->set('token_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('token_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(GrantTypeMiddleware::class)
        ->args([service(GrantTypeManager::class)])
    ;

    $container->set(TokenEndpointExtensionManager::class);

    $container->set(TokenEndpoint::class)
        ->args([
            service(ClientRepository::class),
            service(UserAccountRepository::class)->nullOnInvalid(),
            service(TokenEndpointExtensionManager::class),
            service(ResponseFactoryInterface::class),
            service(AccessTokenRepository::class),
            '%oauth2_server.access_token_lifetime%',
        ])
    ;
};
