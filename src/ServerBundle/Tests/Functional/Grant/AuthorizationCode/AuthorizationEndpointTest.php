<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\Functional\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\UserAccount;
use function Safe\sprintf;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @group ServerBundle
 * @group Functional
 * @group Grant
 * @group AuthorizationCode
 *
 * @internal
 * @coversNothing
 */
class AuthorizationEndpointTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (!class_exists(AuthorizationCodeGrantType::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-code-grant" is not installed.');
        }
        parent::setUp();
    }

    /**
     * @test
     */
    public function theRequestIsValidAndConsentHasBeenGivenToTheClient()
    {
        $uri = $this->buildUri([
            'client_id' => 'CLIENT_ID_2',
            'redirect_uri' => 'https://example.com/cb/?foo=bar',
            'response_type' => 'code',
            'state' => '__STATE__',
        ]);
        $client = static::createClient();
        $this->logIn(
            $client,
            new UserAccount(
                new UserAccountId('john.1'),
                'admin',
                ['ROLE_ADMIN', 'ROLE_USER'],
                new \DateTimeImmutable('now -25 hours'),
                new \DateTimeImmutable('now -15 days'),
                [
                    'address', [
                        'street_address' => '5 rue Sainte Anne',
                        'region' => 'Île de France',
                        'postal_code' => '75001',
                        'locality' => 'Paris',
                        'country' => 'France',
                    ],
                    'name' => 'John Doe',
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                    'middle_name' => 'Jack',
                    'nickname' => 'Little John',
                    'profile' => 'https://profile.doe.fr/john/',
                    'preferred_username' => 'j-d',
                    'gender' => 'M',
                    'phone_number' => '+0123456789',
                    'phone_number_verified' => true,
                    'zoneinfo' => 'Europe/Paris',
                    'locale' => 'en',
                    'picture' => 'https://www.google.com',
                    'birthdate' => '1950-01-01',
                    'email' => 'root@localhost.com',
                    'email_verified' => false,
                    'website' => 'https://john.doe.com',
                    'website#fr_fr' => 'https://john.doe.fr',
                    'website#fr' => 'https://john.doe.fr',
                    'picture#de' => 'https://john.doe.de/picture',
                ]
            )
        );
        $client->request('GET', $uri, [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();

        static::assertEquals(307, $response->getStatusCode());

        $client->followRedirect();
        $response = $client->getResponse();
        static::assertTrue($response->headers->has('location'));
        $location = $response->headers->get('location');
        static::assertIsString($location);
        static::assertStringStartsWith('https://example.com/cb/?foo=bar&state=__STATE__&code=', $location);
        static::assertStringEndsWith('#_=_', $location);
    }

    /**
     * @test
     */
    public function theRequestIsValidAndConsentIsNeede()
    {
        $uri = $this->buildUri([
            'client_id' => 'CLIENT_ID_2',
            'redirect_uri' => 'https://example.com/cb/?foo=bar',
            'response_type' => 'code',
            'state' => '__STATE__',
            'prompt' => 'consent',
        ]);
        $client = static::createClient();
        $this->logIn(
            $client,
            new UserAccount(
                new UserAccountId('john.1'),
                'admin',
                ['ROLE_ADMIN', 'ROLE_USER'],
                new \DateTimeImmutable('now -25 hours'),
                new \DateTimeImmutable('now -15 days'),
                [
                    'address', [
                        'street_address' => '5 rue Sainte Anne',
                        'region' => 'Île de France',
                        'postal_code' => '75001',
                        'locality' => 'Paris',
                        'country' => 'France',
                    ],
                    'name' => 'John Doe',
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                    'middle_name' => 'Jack',
                    'nickname' => 'Little John',
                    'profile' => 'https://profile.doe.fr/john/',
                    'preferred_username' => 'j-d',
                    'gender' => 'M',
                    'phone_number' => '+0123456789',
                    'phone_number_verified' => true,
                    'zoneinfo' => 'Europe/Paris',
                    'locale' => 'en',
                    'picture' => 'https://www.google.com',
                    'birthdate' => '1950-01-01',
                    'email' => 'root@localhost.com',
                    'email_verified' => false,
                    'website' => 'https://john.doe.com',
                    'website#fr_fr' => 'https://john.doe.fr',
                    'website#fr' => 'https://john.doe.fr',
                    'picture#de' => 'https://john.doe.de/picture',
                ]
            )
        );
        $client->request('GET', $uri, [], [], ['HTTPS' => 'on'], null);
        $response = $client->getResponse();

        static::assertEquals(307, $response->getStatusCode());

        $client->followRedirect();
        $response = $client->getResponse();
        static::assertEquals('You are on the consent page', $response->getContent());
    }

    private function buildUri(array $query): string
    {
        $query = http_build_query($query);

        return empty($query) ? '/authorize' : sprintf('/authorize?%s', $query);
    }

    private function logIn(KernelBrowser $client, UserAccount $userAccount): void
    {
        $session = $client->getContainer()->get('session');

        $firewallName = 'main';
        $firewallContext = 'main';

        $token = new UsernamePasswordToken($userAccount, null, $firewallName, ['ROLE_ADMIN']);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}
