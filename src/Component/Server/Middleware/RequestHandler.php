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

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback function (\Psr\Http\Message\RequestInterface $request) : \Psr\Http\Message\ResponseInterface
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        return call_user_func($this->callback, $request);
    }
}
