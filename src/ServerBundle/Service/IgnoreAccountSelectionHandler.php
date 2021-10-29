<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Service;

use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler as SelectAccountHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IgnoreAccountSelectionHandler implements SelectAccountHandlerInterface
{
    public function handle(ServerRequestInterface $request, string $authorizationId): ?ResponseInterface
    {
        return null;
    }
}
