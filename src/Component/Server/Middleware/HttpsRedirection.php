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

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpsRedirection implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * HttpsRedirection constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (!$this->isRequestSecured($request)) {
            $response = $this->responseFactory->createResponse(302);
            $uri = $request->getUri();
            $uri = $uri->withScheme('https');
            $response->withHeader('Location', $uri->__toString());

            return $response;
        }

        return $delegate->process($request);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return bool
     */
    private function isRequestSecured(ServerRequestInterface $request): bool
    {
        $server_params = $request->getServerParams();

        return !empty($server_params['HTTPS']) && 'on' === mb_strtolower($server_params['HTTPS'], '8bit');
    }
}
