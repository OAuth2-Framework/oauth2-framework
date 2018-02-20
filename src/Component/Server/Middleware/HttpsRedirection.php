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

namespace OAuth2Framework\Component\Server\Middleware;

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpsRedirection implements MiddlewareInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * HttpsRedirection constructor.
     *
     * @param MessageFactory $messageFactory
     */
    public function __construct(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        if (!$this->isRequestSecured($request)) {
            $response = $this->messageFactory->createResponse(302);
            $uri = $request->getUri();
            $uri = $uri->withScheme('https');
            $response->withHeader('Location', $uri->__toString());

            return $response;
        }

        return $requestHandler->handle($request);
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
