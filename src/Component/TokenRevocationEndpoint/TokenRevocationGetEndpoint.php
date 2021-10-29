<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationGetEndpoint extends TokenRevocationEndpoint
{
    public function __construct(
        TokenTypeHintManager $tokenTypeHintManager,
        ResponseFactoryInterface $responseFactory,
        private bool $allowJson
    ) {
        parent::__construct($tokenTypeHintManager, $responseFactory);
    }

    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = $request->getQueryParams();
        $supported_parameters = ['token', 'token_type_hint'];
        if ($this->allowJson === true) {
            $supported_parameters[] = 'callback';
        }

        return array_intersect_key($parameters, array_flip($supported_parameters));
    }
}
