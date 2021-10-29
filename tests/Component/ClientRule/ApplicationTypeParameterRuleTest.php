<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\ApplicationTypeParametersRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ApplicationTypeParameterRuleTest extends TestCase
{
    /**
     * @test
     */
    public function applicationTypeParameterRuleSetAsDefault(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([]);
        $rule = new ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('application_type'));
        static::assertSame('web', $validatedParameters->get('application_type'));
    }

    /**
     * @test
     */
    public function applicationTypeParameterRuleDefineInParameters(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'application_type' => 'native',
        ]);
        $rule = new ApplicationTypeParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

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
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'application_type' => 'foo',
        ]);
        $rule = new ApplicationTypeParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
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
