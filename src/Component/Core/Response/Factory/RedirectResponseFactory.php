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

namespace OAuth2Framework\Component\Core\Response\Factory;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\Core\Response\OAuth2RedirectError;
use OAuth2Framework\Component\Core\Response\OAuth2ResponseInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectResponseFactory implements ResponseFactory
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
    public function createResponse(array $data, ResponseInterface &$response): OAuth2ResponseInterface
    {
        if (!array_key_exists('response_mode', $data)) {
            throw new \InvalidArgumentException('The "response_mode" parameter is missing.');
        }
        if (!$data['response_mode'] instanceof ResponseMode) {
            throw new \InvalidArgumentException('The "response_mode" parameter is invalid.');
        }
        if (!array_key_exists('redirect_uri', $data)) {
            throw new \InvalidArgumentException('The "redirect_uri" parameter is missing.');
        }
        if (!is_string($data['redirect_uri'])) {
            throw new \InvalidArgumentException('The "redirect_uri" parameter is invalid.');
        }
        $responseMode = $data['response_mode'];
        $redirectUri = $data['redirect_uri'];

        unset($data['response_mode']);
        unset($data['redirect_uri']);

        return new OAuth2RedirectError(302, $data, $redirectUri, $responseMode, $response);
    }
}
