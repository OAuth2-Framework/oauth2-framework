<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\Message\Factory;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use Psr\Http\Message\ResponseInterface;

final class RedirectResponseFactory implements ResponseFactory
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedCode(): int
    {
        return 302;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(array $data, ResponseInterface $response): ResponseInterface
    {
        if (!\array_key_exists('response_mode', $data) || !$data['response_mode'] instanceof ResponseMode) {
            throw new \InvalidArgumentException('The "response_mode" parameter is missing or invalid.');
        }
        if (!\array_key_exists('redirect_uri', $data) || !\is_string($data['redirect_uri'])) {
            throw new \InvalidArgumentException('The "redirect_uri" parameter is missing or invalid.');
        }

        /** @var ResponseMode $responseMode */
        $responseMode = $data['response_mode'];
        /** @var string $redirectUri */
        $redirectUri = $data['redirect_uri'];

        unset($data['response_mode']);
        unset($data['redirect_uri']);

        $response = $responseMode->buildResponse($response, $redirectUri, $data);

        return $response;
    }
}
