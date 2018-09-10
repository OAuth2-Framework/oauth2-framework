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

namespace OAuth2Framework\Component\Scope;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;

class ScopeParameterChecker implements ParameterChecker
{
    private $scopeRepository;

    private $scopePolicyManager;

    public function __construct(ScopeRepository $scopeRepository, ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    public function check(Authorization $authorization): Authorization
    {
        try {
            if ($authorization->hasQueryParam('scope')) {
                $requestedScope = $authorization->getQueryParam('scope');
                if (1 !== \preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $requestedScope)) {
                    throw new \InvalidArgumentException('Invalid characters found in the "scope" parameter.');
                }
            } else {
                $requestedScope = '';
            }
            $requestedScope = $this->scopePolicyManager->apply($requestedScope, $authorization->getClient());
            if (empty($requestedScope)) {
                return $authorization;
            }
            $scopes = \explode(' ', $requestedScope);

            $availableScopes = $this->scopeRepository->all();
            if (0 !== \count(\array_diff($scopes, $availableScopes))) {
                throw new \InvalidArgumentException(\sprintf('An unsupported scope was requested. Available scopes for the client are %s.', \implode(', ', $availableScopes)));
            }
            $authorization->getMetadata()->set('scope', \implode(' ', $scopes));
            $authorization->setResponseParameter('scope', \implode(' ', $scopes));

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Message::ERROR_INVALID_SCOPE, $e->getMessage(), $authorization, $e);
        }
    }
}
