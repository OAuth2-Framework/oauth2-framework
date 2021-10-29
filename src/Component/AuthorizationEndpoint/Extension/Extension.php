<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Extension;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;

interface Extension
{
    public function process(ServerRequestInterface $request, AuthorizationRequest $authorization): void;
}
