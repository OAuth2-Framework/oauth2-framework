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
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

abstract class ClientRegistrationManagementRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ?UserAccountId $userAccountId, callable $next): DataBag
    {
        $validatedParameters = $next($commandParameters, $validatedParameters, $userAccountId);
        Assertion::true($validatedParameters->has('client_id'));
        Assertion::string($validatedParameters->get('client_id'));
        $clientId = ClientId::create($validatedParameters->get('client_id'));
        $validatedParameters = $validatedParameters->with('registration_access_token', $this->generateRegistrationAccessToken());
        $validatedParameters = $validatedParameters->with('registration_client_uri', $this->getRegistrationClientUri($clientId));

        return $validatedParameters;
    }

    /**
     * @param ClientId $clientId
     *
     * @return string
     */
    abstract protected function getRegistrationClientUri(ClientId $clientId): string;

    /**
     * @return string
     */
    abstract protected function generateRegistrationAccessToken(): string;
}
