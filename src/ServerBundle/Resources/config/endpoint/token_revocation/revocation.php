<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRevocationTypeHint;
use OAuth2Framework\Component\Core\Middleware\HttpMethodMiddleware;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationGetEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationPostEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHintManager;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('token_revocation_pipe')
        ->class(Pipe::class)
        ->args([[
            service('oauth2_server.message_middleware.for_client_authentication'),
            service('oauth2_server.client_authentication.middleware'),
            service('token_revocation_method_handler'),
        ]])
    ;

    $container->set('token_revocation_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('token_revocation_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set('token_revocation_method_handler')
        ->class(HttpMethodMiddleware::class)
        ->call('add', ['POST', service(TokenRevocationPostEndpoint::class)])
        ->call('add', ['GET', service(TokenRevocationGetEndpoint::class)])
    ;

    $container->set(TokenTypeHintManager::class);

    $container->set(TokenRevocationPostEndpoint::class)
        ->args([service(TokenTypeHintManager::class)])
    ;

    $container->set(TokenRevocationGetEndpoint::class)
        ->args([service(TokenTypeHintManager::class), '%oauth2_server.endpoint.token_revocation.allow_callback%'])
    ;

    $container->set(AccessTokenRevocationTypeHint::class)
        ->args([service(AccessTokenRepository::class)])
    ;
};
