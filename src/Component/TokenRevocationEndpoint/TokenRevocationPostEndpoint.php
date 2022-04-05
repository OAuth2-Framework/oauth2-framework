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

        return array_filter([
            'token' => $parameters->get('token'),
            'token_type_hint' => $parameters->get('token_type_hint'),
        ], static function (null|string $item): bool {
            return $item !== null;
        });
    }
}
