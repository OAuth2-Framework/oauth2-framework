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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Processor;

use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use Psr\Http\Message\ServerRequestInterface;

final class ProcessorManager
{
    /**
     * @var callable
     */
    private $processors = [];

    /**
     * @param callable $processor
     *
     * @return ProcessorManager
     */
    public function add(callable $processor): self
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantType              $grantType
     *
     * @return GrantTypeData
     *
     * @throws OAuth2Exception
     */
    public function handle(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData
    {
        $grantTypeData = call_user_func($this->resolve(0), $request, $grantTypeData, $grantType);

        return $grantType->grant($request, $grantTypeData);
    }

    /**
     * @param int $index
     *
     * @return callable
     */
    private function resolve(int $index): callable
    {
        if (!isset($this->processors[$index])) {
            return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
                return $grantTypeData;
            };
        }
        $processor = $this->processors[$index];

        return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType) use ($processor, $index): GrantTypeData {
            return $processor($request, $grantTypeData, $grantType, $this->resolve($index + 1));
        };
    }
}
