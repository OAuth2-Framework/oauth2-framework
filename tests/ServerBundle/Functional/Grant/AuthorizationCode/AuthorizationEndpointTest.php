<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\ServerBundle\Functional\Grant\AuthorizationCode;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
final class AuthorizationEndpointTest extends WebTestCase
{
    /**
     * @test
     */
    public function theRequestIsValidAndConsentHasBeenGivenToTheClient(): void
    {
        $uri = $this->buildUri([
            'client_id' => 'CLIENT_ID_2',
            'redirect_uri' => 'https://example.com/cb/?foo=bar',
            'response_type' => 'code',
            'state' => '__STATE__',
        ]);
        $client = static::createClient();
        $client->loginUser(
            new UserAccount(
                new UserAccountId('john.1'),
                'admin',
                ['ROLE_ADMIN', 'ROLE_USER'],
                new DateTimeImmutable('now -25 hours'),
                new DateTimeImmutable('now -15 days'),
                [
                    'address',
                    [
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
        $client->request('GET', $uri, [], [], [
            'HTTPS' => 'on',
        ], null);
        $response = $client->getResponse();

        static::assertSame(307, $response->getStatusCode());

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
    public function theRequestIsValidAndConsentIsNeeded(): void
    {
        $uri = $this->buildUri([
            'client_id' => 'CLIENT_ID_2',
            'redirect_uri' => 'https://example.com/cb/?foo=bar',
            'response_type' => 'code',
            'state' => '__STATE__',
            'prompt' => 'consent',
        ]);
        $client = static::createClient();
        $client->loginUser(
            new UserAccount(
                new UserAccountId('john.1'),
                'admin',
                ['ROLE_ADMIN', 'ROLE_USER'],
                new DateTimeImmutable('now -25 hours'),
                new DateTimeImmutable('now -15 days'),
                [
                    'address',
                    [
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
        $client->request('GET', $uri, [], [], [
            'HTTPS' => 'on',
        ]);
        $response = $client->getResponse();

        static::assertSame(307, $response->getStatusCode());

        $client->followRedirect();
        $response = $client->getResponse();
        static::assertSame('You are on the consent page', $response->getContent());
    }

    private function buildUri(array $query): string
    {
        $queryParams = http_build_query($query);

        return $queryParams === '' ? '/authorize' : sprintf('/authorize?%s', $queryParams);
    }
}
