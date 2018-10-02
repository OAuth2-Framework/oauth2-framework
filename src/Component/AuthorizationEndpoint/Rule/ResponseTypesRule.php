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

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ResponseTypesRule implements Rule
{
    private $responseTypeManager;

    public function __construct(ResponseTypeManager $responseTypeManager)
    {
        $this->responseTypeManager = $responseTypeManager;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if (!$commandParameters->has('response_types')) {
            $commandParameters->set('response_types', []);
        }
        $this->checkResponseTypes($commandParameters);

        $validatedParameters->set('response_types', $commandParameters->get('response_types'));
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);

        return $validatedParameters;
    }

    private function checkResponseTypes(DataBag $parameters)
    {
        if (!\is_array($parameters->get('response_types'))) {
            throw new \InvalidArgumentException('The parameter "response_types" must be an array of strings.');
        }
        foreach ($parameters->get('response_types') as $grant_type) {
            if (!\is_string($grant_type)) {
                throw new \InvalidArgumentException('The parameter "response_types" must be an array of strings.');
            }
        }
    }
}
