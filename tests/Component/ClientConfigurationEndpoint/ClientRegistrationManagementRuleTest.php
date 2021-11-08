<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientConfigurationEndpoint;

use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ClientRegistrationManagementRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function clientRegistrationManagementRuleSetAsDefault(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new ClientConfigurationRouteRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('registration_access_token'));
        static::assertTrue($validatedParameters->has('registration_client_uri'));
        static::assertSame(
            'https://www.example.com/client/CLIENT_ID',
            $validatedParameters->get('registration_client_uri')
        );
    }

    private function getCallable(): RuleHandler
    {
        return new RuleHandler(function (
            ClientId $clientId,
            DataBag $commandParameters,
            DataBag $validatedParameters
        ): DataBag {
            return $validatedParameters;
        });
    }
}
