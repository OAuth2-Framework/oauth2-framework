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
use OAuth2Framework\Component\Server\Core\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Core\Scope\ScopeRepository;
use Psr\Http\Message\ServerRequestInterface;

final class ProcessorManager
{
    /**
     * @var array
     */
    private $processors = [];

    /**
     * ProcessorManager constructor.
     *
     * @param null|ScopeRepository    $scopeRepository
     * @param null|ScopePolicyManager $scopePolicyManager
     */
    public function __construct(? ScopeRepository $scopeRepository, ? ScopePolicyManager $scopePolicyManager)
    {
        if (null !== $scopeRepository) {
            $this->processors[] = new ScopeProcessor($scopeRepository, $scopePolicyManager);
        }
        $this->processors[] = new TokenTypeProcessor();
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
        $grantTypeData = call_user_func($this->callableForNextRule(0), $request, $grantTypeData, $grantType);

        return $grantType->grant($request, $grantTypeData);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextRule(int $index): \Closure
    {
        if (!isset($this->processors[$index])) {
            return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
                return $grantTypeData;
            };
        }
        $processor = $this->processors[$index];

        return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType) use ($processor, $index): GrantTypeData {
            return $processor($request, $grantTypeData, $grantType, $this->callableForNextRule($index + 1));
        };
    }
}
