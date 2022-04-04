<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\Core\Message\Factory\AccessDeniedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\AuthenticateResponseForClientFactory;
use OAuth2Framework\Component\Core\Message\Factory\AuthenticateResponseForTokenFactory;
use OAuth2Framework\Component\Core\Message\Factory\BadRequestResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\MethodNotAllowedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\NotImplementedResponseFactory;
use OAuth2Framework\Component\Core\Message\Factory\RedirectResponseFactory;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\Middleware\OAuth2MessageMiddleware;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set('oauth2_server.message_middleware.for_client_authentication')
        ->class(OAuth2MessageMiddleware::class)
        ->args([service('oauth2_server.message_factory_manager.for_client_authentication')])
    ;
    $container->set('oauth2_server.message_factory_manager.for_client_authentication')
        ->class(OAuth2MessageFactoryManager::class)
    ;

    $container->set('oauth2_server.message_middleware.for_token_authentication')
        ->class(OAuth2MessageMiddleware::class)
        ->args([service('oauth2_server.message_factory_manager.for_token_authentication')])
    ;
    $container->set('oauth2_server.message_factory_manager.for_token_authentication')
        ->class(OAuth2MessageFactoryManager::class)
    ;

    //Factories
    $container->set('oauth2_server.message_factory.403')
        ->class(AccessDeniedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.401_for_token')
        ->args([service(TokenTypeManager::class)])
        ->class(AuthenticateResponseForTokenFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
    ;

    $container->set('oauth2_server.message_factory.401_for_client')
        ->args([service(AuthenticationMethodManager::class)])
        ->class(AuthenticateResponseForClientFactory::class)
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.400')
        ->class(BadRequestResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.405')
        ->class(MethodNotAllowedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.501')
        ->class(NotImplementedResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;

    $container->set('oauth2_server.message_factory.303')
        ->class(RedirectResponseFactory::class)
        ->tag('oauth2_server_message_factory_for_token_authentication')
        ->tag('oauth2_server_message_factory_for_client_authentication')
    ;
};
