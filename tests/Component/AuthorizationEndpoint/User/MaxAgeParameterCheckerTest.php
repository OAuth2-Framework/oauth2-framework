<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\User;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\User\MaxAgeParameterAuthenticationChecker;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\Client;
use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;

/**
 * @internal
 */
final class MaxAgeParameterCheckerTest extends OAuth2TestCase
{
    /**
     * @test
     * @dataProvider useCases
     */
    public function theUserHasNeverBeenConnected(
        array $clientConfiguration,
        array $authorizationParameters,
        ?DateTimeImmutable $lastLogin,
        bool $expectedResult
    ): void {
        $client = Client::create(
            ClientId::create('CLIENT_ID'),
            DataBag::create($clientConfiguration),
            UserAccountId::create('john.1')
        );

        $userAccount = UserAccount::create(UserAccountId::create('john.1'), 'john.1', [], $lastLogin, null, []);

        $authorization = AuthorizationRequest::create($client, $authorizationParameters)
            ->setUserAccount($userAccount)
        ;
        $checker = MaxAgeParameterAuthenticationChecker::create();

        static::assertSame($expectedResult, $checker->isAuthenticationNeeded($authorization));
    }

    public function useCases(): array
    {
        return [
            [[], [], null, false],
            [
                [
                    'default_max_age' => 90,
                ],
                [],
                new DateTimeImmutable('now -100 seconds'),
                true,
            ],
            [
                [
                    'default_max_age' => 3600,
                ],
                [],
                new DateTimeImmutable('now -100 seconds'),
                false,
            ],
            [
                [
                    'default_max_age' => 3600,
                ],
                [
                    'max_age' => 110,
                ],
                new DateTimeImmutable('now -100 seconds'),
                false,
            ],
            [
                [
                    'default_max_age' => 3600,
                ],
                [
                    'max_age' => 90,
                ],
                new DateTimeImmutable('now -100 seconds'),
                true,
            ],
            [[], [], new DateTimeImmutable('now -100 seconds'), false],
            [
                [],
                [
                    'max_age' => 3600,
                ],
                null,
                true,
            ],
            [
                [],
                [
                    'max_age' => 3600,
                ],
                new DateTimeImmutable('now -10000 seconds'),
                true,
            ],
        ];
    }
}
