<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use OAuth2Framework\Component\ClientRule\ClientIdIssuedAtRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ClientIdIssuedAtRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function clientIdIssuedAtRuleSetAsDefault(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new ClientIdIssuedAtRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('client_id_issued_at'));
        static::assertIsInt($validatedParameters->get('client_id_issued_at'));
    }

    /**
     * @test
     */
    public function clientIdIssuedAtRuleDefineInParameters(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'client_id_issued_at' => time() - 1000,
        ]);
        $rule = new ClientIdIssuedAtRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('client_id_issued_at'));
        static::assertIsInt($validatedParameters->get('client_id_issued_at'));
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
