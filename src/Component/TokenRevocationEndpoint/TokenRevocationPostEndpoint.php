<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationPostEndpoint extends TokenRevocationEndpoint
{
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);

        return array_intersect_key($parameters, array_flip(['token', 'token_type_hint']));
    }
}
