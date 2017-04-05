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

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;

final class GrantTypeFlowRule implements RuleInterface
{
    /**
     * @var GrantTypeManager
     */
    private $grantTypeManager;

    /**
     * @var ResponseTypeManager
     */
    private $responseTypeManager;

    /**
     * GrantTypeFlowRule constructor.
     *
     * @param GrantTypeManager    $grantTypeManager
     * @param ResponseTypeManager $responseTypeManager
     */
    public function __construct(GrantTypeManager $grantTypeManager, ResponseTypeManager $responseTypeManager)
    {
        $this->grantTypeManager = $grantTypeManager;
        $this->responseTypeManager = $responseTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if (!$commandParameters->has('grant_types')) {
            $commandParameters = $commandParameters->with('grant_types', []);
        }
        if (!$commandParameters->has('response_types')) {
            $commandParameters = $commandParameters->with('response_types', []);
        }
        $this->checkGrantTypes($commandParameters);
        $this->checkResponseTypes($commandParameters);

        $validatedParameters = $validatedParameters->with('grant_types', $commandParameters->get('grant_types'));
        $validatedParameters = $validatedParameters->with('response_types', $commandParameters->get('response_types'));

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkGrantTypes(DataBag $parameters)
    {
        Assertion::isArray($parameters->get('grant_types'), 'The parameter \'grant_types\' must be an array of strings.');
        Assertion::allString($parameters->get('grant_types'), 'The parameter \'grant_types\' must be an array of strings.');

        foreach ($parameters->get('grant_types') as $grant_type) {
            $type = $this->grantTypeManager->get($grant_type);
            $associated_response_types = $type->getAssociatedResponseTypes();
            $diff = array_diff($associated_response_types, $parameters->get('response_types'));
            Assertion::true(empty($diff), sprintf('The grant type \'%s\' is associated with the response types \'%s\' but this response type is missing.', $type->getGrantType(), implode(', ', $diff)));
        }
    }

    /**
     * @param DataBag $parameters
     *
     * @throws \InvalidArgumentException
     */
    private function checkResponseTypes(DataBag $parameters)
    {
        Assertion::isArray($parameters->get('response_types'), 'The parameter \'response_types\' must be an array of strings.');
        Assertion::allString($parameters->get('response_types'), 'The parameter \'response_types\' must be an array of strings.');

        foreach ($parameters->get('response_types') as $response_type) {
            $types = $this->responseTypeManager->find($response_type);
            foreach ($types as $type) {
                $associated_grant_types = $type->getAssociatedGrantTypes();
                $diff = array_diff($associated_grant_types, $parameters->get('grant_types'));
                Assertion::true(empty($diff), sprintf('The response type \'%s\' is associated with the grant types \'%s\' but this response type is missing.', $type->getResponseType(), implode(', ', $diff)));
            }
        }
    }
}
