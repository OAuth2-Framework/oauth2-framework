<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final class IFrameEndpoint implements MiddlewareInterface
{
    public function __construct(
        private readonly Environment $templateEngine,
        private readonly string $template,
        private readonly string $storageName
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $content = $this->templateEngine->render($this->template, [
            'storage_name' => $this->storageName,
        ]);
        $headers = [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()
            ->write($content)
        ;

        return $response;
    }
}
