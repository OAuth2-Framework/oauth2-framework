<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\ContactsParametersRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Tests\Component\OAuth2TestCase;

/**
 * @internal
 */
final class ContactsParametersRuleTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function theContactsParameterIsNotAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'contacts' => 123,
        ]);
        $rule = new ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsNotAnArrayOfEmailAddresses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'contacts' => [123],
        ]);
        $rule = new ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsValid(): void
    {
        $clientId = ClientId::create('CLIENT_ID');
        $commandParameters = DataBag::create([
            'contacts' => ['foo@bar.com', 'hello@you.com'],
        ]);
        $rule = new ContactsParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, DataBag::create([]), $this->getCallable());

        static::assertTrue($validatedParameters->has('contacts'));
        static::assertSame(['foo@bar.com', 'hello@you.com'], $validatedParameters->get('contacts'));
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
