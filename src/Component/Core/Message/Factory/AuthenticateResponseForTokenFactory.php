<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\Message\Factory;

use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Psr\Http\Message\ResponseInterface;

final class AuthenticateResponseForTokenFactory extends OAuth2ResponseFactory
{
    private TokenTypeManager $tokenTypeManager;

    public function __construct(TokenTypeManager $tokenTypeManager)
    {
        $this->tokenTypeManager = $tokenTypeManager;
    }

    public function getSupportedCode(): int
    {
        return 401;
    }

    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        $response = $response->withStatus(
            $this->getSupportedCode()
        );

        $schemes = $this->tokenTypeManager->getSchemes($data);
        $headers = [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'WWW-Authenticate' => $schemes,
        ];

        return $this->updateHeaders($headers, $response);
    }
}
