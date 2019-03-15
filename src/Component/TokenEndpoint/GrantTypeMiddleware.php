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

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GrantTypeMiddleware implements MiddlewareInterface
{
    /**
     * @var GrantTypeManager
     */
    private $grantTypeManager;

    public function __construct(GrantTypeManager $grantTypeManager)
    {
        $this->grantTypeManager = $grantTypeManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $parameters = RequestBodyParser::parseFormUrlEncoded($request);
            if (!\array_key_exists('grant_type', $parameters)) {
                throw new \InvalidArgumentException('The "grant_type" parameter is missing.');
            }
            $grant_type = $parameters['grant_type'];
            if (!$this->grantTypeManager->has($grant_type)) {
                throw new \InvalidArgumentException(\Safe\sprintf('The grant type "%s" is not supported by this server.', $grant_type));
            }
            $type = $this->grantTypeManager->get($grant_type);
            $request = $request->withAttribute('grant_type', $type);

            return $handler->handle($request);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }
    }
}
