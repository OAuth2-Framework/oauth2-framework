<?php

declare(strict_types=1);

use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\ServerBundle\Controller\MetadataController;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set('metadata_pipe')
        ->class(Pipe::class)
        ->args([[service(MetadataController::class)]])
    ;

    $container->set('metadata_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('metadata_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(MetadataController::class)
        ->args([service(MetadataBuilder::class)])
    ;

    $container->set(MetadataBuilder::class)
        ->args([service('router')])
    ;
};
