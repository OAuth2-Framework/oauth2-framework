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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

/**
 * Class StateParameterChecker.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
 */
final class StateParameterChecker implements ParameterChecker
{
    /**
     * @var bool
     */
    private $stateParameterEnforced = false;

    /**
     * StateParameterChecker constructor.
     *
     * @param bool $stateParameterEnforced
     */
    public function __construct(bool $stateParameterEnforced)
    {
        $this->stateParameterEnforced = $stateParameterEnforced;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if (true === $this->stateParameterEnforced) {
                Assertion::true($authorization->hasQueryParam('state'), 'The parameter "state" is mandatory.');
            }
            if (true === $authorization->hasQueryParam('state')) {
                $authorization = $authorization->withResponseParameter('state', $authorization->getQueryParam('state'));
            }

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}
