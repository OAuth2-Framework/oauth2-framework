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

namespace OAuth2Framework\Component\Server\Response\Factory;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Response\OAuth2RedirectError;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseInterface;
use OAuth2Framework\Component\Server\ResponseMode\ResponseModeInterface;
use Psr\Http\Message\ResponseInterface;

final class RedirectResponseFactory implements ResponseFactoryInterface
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
        Assertion::keyExists($data, 'response_mode', 'The \'response_mode\' parameter is missing.');
        Assertion::keyExists($data, 'redirect_uri', 'The \'redirect_uri\' parameter is missing.');
        Assertion::isInstanceOf($data['response_mode'], ResponseModeInterface::class, 'The \'response_mode\' parameter is invalid.');
        Assertion::string($data['redirect_uri'], 'The \'redirect_uri\' parameter is invalid.');
        $responseMode = $data['response_mode'];
        $redirectUri = $data['redirect_uri'];

        unset($data['response_mode']);
        unset($data['redirect_uri']);

        return new OAuth2RedirectError(302, $data, $redirectUri, $responseMode, $response);
    }
}
