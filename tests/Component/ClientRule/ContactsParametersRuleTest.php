<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\ClientRule;

use InvalidArgumentException;
use OAuth2Framework\Component\ClientRule\ContactsParametersRule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ContactsParametersRuleTest extends TestCase
{
    /**
     * @test
     */
    public function theContactsParameterIsNotAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => 123,
        ]);
        $rule = new ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsNotAnArrayOfEmailAddresses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter "contacts" must be a list of e-mail addresses.');
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => [123],
        ]);
        $rule = new ContactsParametersRule();
        $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());
    }

    /**
     * @test
     */
    public function theContactsParameterIsValid(): void
    {
        $clientId = new ClientId('CLIENT_ID');
        $commandParameters = new DataBag([
            'contacts' => ['foo@bar.com', 'hello@you.com'],
        ]);
        $rule = new ContactsParametersRule();
        $validatedParameters = $rule->handle($clientId, $commandParameters, new DataBag([]), $this->getCallable());

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
