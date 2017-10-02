<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\TokenRevocation;

use Http\Message\MessageFactory;
use OAuth2Framework\Component\Server\TokenTypeHint\TokenTypeHintManager;
use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationGetEndpoint extends TokenRevocationEndpoint
{
    /**
     * @var bool
     */
    private $allowJson;

    /**
     * TokenRevocationGetEndpoint constructor.
     *
     * @param TokenTypeHintManager     $tokenTypeHintManager
     * @param MessageFactory $messageFactory
     * @param bool                     $allowJson
     */
    public function __construct(TokenTypeHintManager $tokenTypeHintManager, MessageFactory $messageFactory, bool $allowJson)
    {
        parent::__construct($tokenTypeHintManager, $messageFactory);
        $this->allowJson = $allowJson;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = $request->getQueryParams();
        $supported_parameters = ['token', 'token_type_hint'];
        if (true === $this->allowJson) {
            $supported_parameters[] = 'callback';
        }

        return array_intersect_key($parameters, array_flip($supported_parameters));
    }
}
