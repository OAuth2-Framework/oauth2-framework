<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyDefaultRule;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ScopePolicyDefaultRuleTest extends OAuth2TestCase
{
    /**
     * @inheritdoc}
     */
    protected function setUp(): void
    {
        if (! interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client" is not installed.');
        }
    }

    /**
     * @test
     */
    public function theParameterMustBeAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "default_scope" parameter must be a string.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => ['foo'],
        ]);
        $rule = new ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterContainsForbiddenCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters found in the "default_scope" parameter.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => 'coffee, café',
        ]);
        $rule = new ScopePolicyDefaultRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'default_scope' => 'coffee cream',
        ]);
        $rule = new ScopePolicyDefaultRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('default_scope'));
        static::assertSame('coffee cream', $validatedParameters->get('default_scope'));
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
