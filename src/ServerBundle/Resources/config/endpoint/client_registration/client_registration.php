<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientRegistrationEndpoint\ClientRegistrationEndpoint;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('client_registration_pipe')
        ->class(Pipe::class)
        ->args([[
            service('oauth2_server.message_middleware.for_client_registration'),
            service('oauth2_server.client_registration.endpoint'),
        ]])
    ;

    $container->set('client_registration_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('client_registration_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set('oauth2_server.client_registration.endpoint')
        ->class(ClientRegistrationEndpoint::class)
        ->args([service(ClientRepository::class), service(RuleManager::class)])
    ;

    $container->set('oauth2_server.message_middleware.for_client_registration')
        ->class(OAuth2MessageMiddleware::class)
        ->args([service('oauth2_server.message_factory_manager.for_client_registration')])
    ;
    $container->set('oauth2_server.message_factory_manager.for_client_registration')
        ->class(OAuth2MessageFactoryManager::class)
        ->call('addFactory', [service('oauth2_server.message_factory.303')])
        ->call('addFactory', [service('oauth2_server.message_factory.400')])
        ->call('addFactory', [service('oauth2_server.message_factory.403')])
        ->call('addFactory', [service('oauth2_server.message_factory.405')])
        ->call('addFactory', [service('oauth2_server.message_factory.501')])
    ;
};
