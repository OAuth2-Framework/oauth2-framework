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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeManager;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class ResponseTypesRule implements Rule
{
    /**
     * @var ResponseTypeManager
     */
    private $responseTypeManager;

    /**
     * ResponseTypesRule constructor.
     *
     * @param ResponseTypeManager $responseTypeManager
     */
    public function __construct(ResponseTypeManager $responseTypeManager)
    {
        $this->responseTypeManager = $responseTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if (!$commandParameters->has('response_types')) {
            $commandParameters = $commandParameters->with('response_types', []);
        }
        $this->checkResponseTypes($commandParameters);

        $validatedParameters = $validatedParameters->with('response_types', $commandParameters->get('response_types'));
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);
        $this->checkGrantTypes($validatedParameters);

        return $validatedParameters;
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkResponseTypes(DataBag $parameters)
    {
        if (!is_array($parameters->get('response_types'))) {
            throw new \InvalidArgumentException('The parameter "response_types" must be an array of strings.');
        }
        foreach ($parameters->get('response_types') as $grant_type) {
            if (!is_string($grant_type)) {
                throw new \InvalidArgumentException('The parameter "response_types" must be an array of strings.');
            }
        }
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkGrantTypes(DataBag $parameters)
    {
        $grantTypes = $parameters->has('grant_types') ? $parameters->get('grant_types') : [];
        foreach ($parameters->get('response_types') as $responseType) {
            $type = $this->responseTypeManager->get($responseType);
            $diff = array_diff($type->associatedGrantTypes(), $grantTypes);
            if (!empty($diff)) {
                throw new \InvalidArgumentException(sprintf('The response type "%s" requires the following grant type(s): %s.', $responseType, implode(', ', $diff)));
            }
        }
    }
}
