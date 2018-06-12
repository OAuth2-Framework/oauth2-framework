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

namespace OAuth2Framework\Component\TokenEndpoint\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;

final class GrantTypesRule implements Rule
{
    /**
     * @var GrantTypeManager
     */
    private $grantTypeManager;

    /**
     * GrantTypeFlowRule constructor.
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
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if (!$commandParameters->has('grant_types')) {
            $commandParameters = $commandParameters->with('grant_types', []);
        }
        $this->checkGrantTypes($commandParameters);
        $validatedParameters->with('grant_types', $commandParameters->get('grant_types'));
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);
        //$this->checkResponseTypes($validatedParameters);

        return $validatedParameters;
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkGrantTypes(DataBag $parameters)
    {
        if (!is_array($parameters->get('grant_types'))) {
            throw new \InvalidArgumentException('The parameter "grant_types" must be an array of strings.');
        }
        foreach ($parameters->get('grant_types') as $grant_type) {
            if (!is_string($grant_type)) {
                throw new \InvalidArgumentException('The parameter "grant_types" must be an array of strings.');
            }
            if (!$this->grantTypeManager->has($grant_type)) {
                throw new \InvalidArgumentException(sprintf('The grant_type "%s" is not supported by this server.', $grant_type));
            }
        }
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkResponseTypes(DataBag $parameters)
    {
        $responseTypes = $parameters->has('response_types') ? $parameters->get('response_types') : [];
        $list = [];
        foreach ($responseTypes as $responseType) {
            $list = array_merge(
                $list,
                explode(' ', $responseType)
            );
        }
        foreach ($parameters->get('grant_types') as $grantType) {
            $type = $this->grantTypeManager->get($grantType);
            $diff = array_diff($type->associatedResponseTypes(), $list);
            if (!empty($diff)) {
                throw new \InvalidArgumentException(sprintf('The grant type "%s" requires the following response type(s): %s.', $grantType, implode(', ', $diff)));
            }
        }
    }
}
