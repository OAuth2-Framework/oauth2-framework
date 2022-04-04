<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Rule\ScopeRule;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ScopeRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theParameterMustBeAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "scope" parameter must be a string.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope' => ['foo'],
        ]);
        $rule = new ScopeRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterContainsForbiddenCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters found in the "scope" parameter.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope' => 'coffee, café',
        ]);
        $rule = new ScopeRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope' => 'coffee cream',
        ]);
        $rule = new ScopeRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('scope'));
        static::assertSame('coffee cream', $validatedParameters->get('scope'));
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
