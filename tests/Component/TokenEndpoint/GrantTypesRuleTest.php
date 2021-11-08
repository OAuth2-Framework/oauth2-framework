<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\TokenEndpoint;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\TokenEndpoint\Rule\GrantTypesRule;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class GrantTypesRuleTest extends OAuth2TestCase
{
    private ?GrantTypesRule $grantTypesRule = null;

    protected function setUp(): void
    {
        if (! interface_exists(Rule::class)) {
            static::markTestSkipped('The component "oauth2-framework/client-rule" is not installed.');
        }
    }

    /**
     * @test
     */
    public function grantTypesSetAsAnEmptyArray(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertSame([], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     */
    public function grantTypesCorrectlyDefined(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = DataBag::create([
            'response_types' => ['code'],
        ]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertSame(['authorization_code'], $validatedParameters->get('grant_types'));
    }

    /**
     * @test
     */
    public function theGrantTypeParameterMustBeAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "grant_types" must be an array of strings.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => 'hello',
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theGrantTypeParameterMustBeAnArrayOfStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "grant_types" must be an array of strings.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => [123],
        ]);
        $rule = $this->getGrantTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theAssociatedResponseTypesAreSet(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'grant_types' => ['authorization_code'],
        ]);
        $validatedParameters = DataBag::create([
            'response_types' => ['code id_token token'],
        ]);
        $rule = $this->getGrantTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('grant_types'));
        static::assertSame(['authorization_code'], $validatedParameters->get('grant_types'));
        static::assertTrue($validatedParameters->has('response_types'));
        static::assertSame(['code id_token token'], $validatedParameters->get('response_types'));
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

    private function getGrantTypesRule(): GrantTypesRule
    {
        if ($this->grantTypesRule === null) {
            $this->grantTypesRule = new GrantTypesRule($this->getGrantTypeManager());
        }

        return $this->grantTypesRule;
    }
}
