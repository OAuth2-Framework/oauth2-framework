<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyRule;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ScopePolicyRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theParameterMustBeAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "scope_policy" must be a string.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => ['foo'],
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theScopePolicyIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The scope policy "foo" is not supported.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => 'foo',
        ]);
        $rule = $this->getScopePolicyRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theParameterIsValid(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'scope_policy' => 'none',
        ]);
        $rule = $this->getScopePolicyRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
        static::assertTrue($validatedParameters->has('scope_policy'));
        static::assertSame('none', $validatedParameters->get('scope_policy'));
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

    private function getScopePolicyRule(): ScopePolicyRule
    {
        $scopePolicyManager = new ScopePolicyManager();
        $scopePolicyManager->add(new NoScopePolicy());

        return new ScopePolicyRule($scopePolicyManager);
    }
}
