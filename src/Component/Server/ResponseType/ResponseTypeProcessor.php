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

namespace OAuth2Framework\Component\Server\ResponseType;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;

final class ResponseTypeProcessor
{
    /**
     * @var ResponseTypeInterface[]
     */
    private $responseTypes;

    /**
     * @var Authorization
     */
    private $authorization;

    /**
     * ResponseTypeProcessor constructor.
     *
     * @param Authorization $authorization
     */
    private function __construct(Authorization $authorization)
    {
        $this->authorization = $authorization;
        $this->responseTypes = $authorization->getResponseTypes();
    }

    /**
     * @param Authorization $authorization
     *
     * @return ResponseTypeProcessor
     */
    public static function create(Authorization $authorization): ResponseTypeProcessor
    {
        return new self($authorization);
    }

    /**
     * @return Authorization
     */
    public function process(): Authorization
    {
        return call_user_func($this->callableForNextRule(0), $this->authorization);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextRule(int $index): \Closure
    {
        if (!isset($this->responseTypes[$index])) {
            return function (Authorization $authorization) {
                return $authorization;
            };
        }
        $responseType = $this->responseTypes[$index];

        return function (Authorization $authorization) use ($responseType, $index): Authorization {
            return $responseType->process($authorization, $this->callableForNextRule($index + 1));
        };
    }
}
