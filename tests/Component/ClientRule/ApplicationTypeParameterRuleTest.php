<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\ApplicationTypeParametersRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ApplicationTypeParameterRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function applicationTypeParameterRuleSetAsDefault(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = new ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('application_type'));
        static::assertSame('web', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function applicationTypeParameterRuleDefineInParameters(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'application_type' => 'native',
        ]);
        $rule = new ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('application_type'));
        static::assertSame('native', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function applicationTypeParameterRule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "application_type" must be either "native" or "web".');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'application_type' => 'foo',
        ]);
        $rule = new ApplicationTypeParametersRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
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
