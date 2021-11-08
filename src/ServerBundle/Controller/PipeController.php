<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Controller;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\Middleware\TerminalRequestHandler;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PipeController
{
    public function __construct(
        private Pipe $pipe
    ) {
    }

    public function handle(Request $symfonyRequest): Response
    {
        $httpFoundationFactory = new HttpFoundationFactory();
        $psrFactory = new PsrHttpFactory(
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory()
        );
        $psrRequest = $psrFactory->createRequest($symfonyRequest);

        $psrResponse = $this->pipe->process($psrRequest, new TerminalRequestHandler());

        return $httpFoundationFactory->createResponse($psrResponse);
    }
}
