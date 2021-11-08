<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use OAuth2Framework\ServerBundle\Service\IFrameEndpoint;
use OAuth2Framework\ServerBundle\Service\SessionStateParameterExtension;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\HttpFoundation\RequestStack;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('oauth2_server.endpoint.session_management_pipe')
        ->class(Pipe::class)
        ->args([[service(IFrameEndpoint::class)]])
    ;

    $container->set('oauth2_server.endpoint.session_management_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('oauth2_server.endpoint.session_management_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(IFrameEndpoint::class)
        ->args([
            service('twig'),
            service(ResponseFactoryInterface::class),
            '%oauth2_server.endpoint.session_management.template%',
            '%oauth2_server.endpoint.session_management.storage_name%',
        ])
    ;

    $container->set(SessionStateParameterExtension::class)
        ->args([service(RequestStack::class), '%oauth2_server.endpoint.session_management.storage_name%'])
        ->tag('oauth2_server_after_consent_screen')
    ;
};
