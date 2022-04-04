<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\Rule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ResponseTypesRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function responseTypesSetAsAnEmptyArray(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('response_types'));
        static::assertSame([], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     */
    public function responseTypesCorrectlyDefined(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => ['code', 'id_token'],
        ]);
        $validatedParameters = DataBag::create([
            'grant_types' => ['authorization_code'],
        ]);
        $rule = $this->getResponseTypesRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, $validatedParameters, $this->getCallable());

        static::assertTrue($validatedParameters->has('response_types'));
        static::assertSame(['code', 'id_token'], $validatedParameters->get('response_types'));
    }

    /**
     * @test
     */
    public function theResponseTypeParameterMustBeAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "response_types" must be an array of strings.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => 'hello',
        ]);
        $rule = $this->getResponseTypesRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theResponseTypeParameterMustBeAnArrayOfStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "response_types" must be an array of strings.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'response_types' => [123],
        ]);
        $rule = $this->getResponseTypesRule();
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
