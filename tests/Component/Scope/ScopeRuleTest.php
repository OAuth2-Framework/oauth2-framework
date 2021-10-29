<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Rule\ScopeRule;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ScopeRuleTest extends TestCase
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
        $this->expectExceptionMessage('The "scope" parameter must be a string.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope' => ['foo'],
        ]);
        $rule = new ScopeRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterContainsForbiddenCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid characters found in the "scope" parameter.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope' => 'coffee, café',
        ]);
        $rule = new ScopeRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'scope' => 'coffee cream',
        ]);
        $rule = new ScopeRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
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
