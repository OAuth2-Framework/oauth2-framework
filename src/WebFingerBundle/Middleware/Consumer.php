<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\Middleware;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class Consumer implements RequestHandlerInterface
{
    public function __construct(
        private readonly Generator $generator,
        private readonly RequestHandlerInterface $delegate
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->generator->valid()) {
            return $this->delegate->handle($request);
        }

        $current = $this->generator->current();
        $this->generator->next();

        return $current->process($request, $this);
    }
}
