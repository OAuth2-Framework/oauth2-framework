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

namespace OAuth2Framework\Component\TokenEndpoint;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GrantTypeMiddleware implements MiddlewareInterface
{
    /**
     * @var GrantTypeManager
     */
    private $grantTypeManager;

    /**
     * GrantTypeMiddleware constructor.
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
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $requestParameters = $request->getParsedBody() ?? [];
            if (!array_key_exists('grant_type', $requestParameters)) {
                throw new \InvalidArgumentException('The "grant_type" parameter is missing.');
            }
            $grant_type = $requestParameters['grant_type'];
            if (!$this->grantTypeManager->has($grant_type)) {
                throw new \InvalidArgumentException(sprintf('The grant type "%s" is not supported by this server.', $grant_type));
            }
            $type = $this->grantTypeManager->get($grant_type);
            $request = $request->withAttribute('grant_type', $type);

            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
        }
    }
}
