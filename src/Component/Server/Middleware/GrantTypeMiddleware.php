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

namespace OAuth2Framework\Component\Server\Middleware;

use Assert\Assertion;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class GrantTypeMiddleware implements MiddlewareInterface
{
    /**
     * @var GrantTypeManager
     */
    private $grantTypeManager;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param GrantTypeManager $grantTypeManager
     */
    public function __construct(GrantTypeManager $grantTypeManager)
    {
        $this->grantTypeManager = $grantTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        try {
            $requestParameters = $request->getParsedBody() ?? [];
            Assertion::keyExists($requestParameters, 'grant_type', 'The \'grant_type\' parameter is missing.');
            $grant_type = $requestParameters['grant_type'];
            Assertion::true($this->grantTypeManager->has($grant_type), sprintf('The grant type \'%s\' is not supported by this server.', $grant_type));
            $type = $this->grantTypeManager->get($grant_type);
            $request = $request->withAttribute('grant_type', $type);

            return $delegate->process($request);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => $e->getMessage(),
                ]
            );
        }
    }
}
