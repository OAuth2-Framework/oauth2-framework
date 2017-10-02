<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Tests\Context;

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelDictionary;
use OAuth2Framework\Bundle\Server\Model\AuthCodeRepository;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Bundle\Server\Model\InitialAccessTokenRepository;
use OAuth2Framework\Bundle\Server\Model\PreConfiguredAuthorizationRepository;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCode;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessToken;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use Symfony\Component\Filesystem\Filesystem;

final class FixturesContext implements Context
{
    use KernelDictionary;

    /**
     * @BeforeScenario
     */
    public function loadFixtures()
    {
        $this->loadClients();
        $this->loadAccessTokens();
        $this->loadRefreshTokens();
        $this->loadAuthorizationCodes();
        $this->loadInitialAccessTokens();
        $this->loadPreConfiguredAuthorizations();
    }

    /**
     * @AfterScenario
     */
    public function removeFixtures()
    {
        $storagePath = sprintf('%s/fixtures', $this->getContainer()->getParameter('kernel.cache_dir'));

        $fs = new Filesystem();
        $fs->remove($storagePath);
    }

    private function loadClients()
    {
        $clientRepository = $this->getContainer()->get(ClientRepository::class);

        foreach ($this->getClients() as $clientInformation) {
            $client = Client::createEmpty();
            $client = $client->create(
                $clientInformation['id'],
                $clientInformation['parameters'],
                $clientInformation['user_account_id']
            );
            if ($clientInformation['is_deleted']) {
                $client = $client->markAsDeleted();
            }
            $clientRepository->save($client);
        }
    }

    /**
     * @return array
     */
    private function getClients(): array
    {
        return [
            [
                'id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client1',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client1',
                    'token_endpoint_auth_method' => 'client_secret_basic',
                    'client_secret' => 'secret',
                    'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code', 'urn:ietf:params:oauth:grant-type:jwt-bearer'],
                    'response_types' => ['code', 'token', 'id_token', 'code token', 'code id_token', 'id_token token', 'code id_token token', 'none'],
                    'redirect_uris' => ['https://example.com/', 'https://example.com/redirection/callback'],
                ]),
                'is_deleted' => false,
            ],
            [
                'id' => ClientId::create('client2'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client2',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client2',
                    'token_endpoint_auth_method' => 'none',
                    'grant_types' => ['client_credentials', 'authorization_code'],
                    'userinfo_signed_response_alg' => 'none',
                ]),
                'is_deleted' => false,
            ],
            [
                'id' => ClientId::create('client3'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client3',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client3',
                    'token_endpoint_auth_method' => 'client_secret_jwt',
                    'client_secret' => 'secret',
                    'client_secret_expires_at' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
                    'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code'],
                ]),
                'is_deleted' => false,
            ],
            [
                'id' => ClientId::create('client4'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client4',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client4',
                    'token_endpoint_auth_method' => 'client_secret_post',
                    'client_secret' => 'secret',
                    'client_secret_expires_at' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
                ]),
                'is_deleted' => false,
            ],
            [
                'id' => ClientId::create('client5'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client4',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client4',
                    'token_endpoint_auth_method' => 'client_secret_post',
                    'client_secret' => 'secret',
                    'client_secret_expires_at' => (new \DateTimeImmutable('now -1 day'))->getTimestamp(),
                ]),
                'is_deleted' => false,
            ],
            [
                'id' => ClientId::create('client6'),
                'user_account_id' => UserAccountId::create('john.1'),
                'parameters' => DataBag::createFromArray([
                    'client_id' => 'client4',
                    'registration_access_token' => 'REGISTRATION_ACCESS_TOKEN',
                    'registration_client_uri' => 'https://oauth2.test/client/configure/client4',
                    'token_endpoint_auth_method' => 'client_secret_post',
                    'client_secret' => 'secret',
                    'client_secret_expires_at' => (new \DateTimeImmutable('now -1 day'))->getTimestamp(),
                ]),
                'is_deleted' => true,
            ],
        ];
    }

    private function loadInitialAccessTokens()
    {
        $manager = $this->getContainer()->get(InitialAccessTokenRepository::class);

        foreach ($this->getInitialAccessTokens() as $initial_access_Token_information) {
            $initialAccessToken = InitialAccessToken::createEmpty();
            $initialAccessToken = $initialAccessToken->create(
                $initial_access_Token_information['id'],
                $initial_access_Token_information['user_account_id'],
                $initial_access_Token_information['expires_at']
            );
            if (true === $initial_access_Token_information['is_revoked']) {
                $initialAccessToken = $initialAccessToken->markAsRevoked();
            }
            $manager->save($initialAccessToken);
        }
    }

    /**
     * @return array
     */
    private function getInitialAccessTokens(): array
    {
        return [
            [
                'id' => InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_VALID'),
                'user_account_id' => UserAccountId::create('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 hour'),
                'is_revoked' => false,
            ],
            [
                'id' => InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_EXPIRED'),
                'user_account_id' => UserAccountId::create('john.1'),
                'expires_at' => new \DateTimeImmutable('now -1 hour'),
                'is_revoked' => false,
            ],
            [
                'id' => InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_REVOKED'),
                'user_account_id' => UserAccountId::create('john.1'),
                'expires_at' => new \DateTimeImmutable('now +1 hour'),
                'is_revoked' => true,
            ],
        ];
    }

    private function loadAccessTokens()
    {
        $manager = $this->getContainer()->get('oauth2_server.access_token.repository');

        foreach ($this->getAccessTokens() as $accessTokenInformation) {
            $accessToken = AccessToken::createEmpty();
            $accessToken = $accessToken->create(
                $accessTokenInformation['id'],
                $accessTokenInformation['resource_owner_id'],
                $accessTokenInformation['client_id'],
                $accessTokenInformation['parameters'],
                $accessTokenInformation['metadatas'],
                $accessTokenInformation['scope'],
                $accessTokenInformation['expires_at'],
                $accessTokenInformation['refresh_token'],
                $accessTokenInformation['resource_server_id']
            );
            $manager->save($accessToken);
        }
    }

    /**
     * @return array
     */
    private function getAccessTokens(): array
    {
        return [
            [
                'id' => AccessTokenId::create('ACCESS_TOKEN_#1'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client1'),
                'parameters' => DataBag::createFromArray(['token_type' => 'Bearer']),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => [],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => ResourceServerId::create('ResourceServer1'),
            ],
            [
                'id' => AccessTokenId::create('ACCESS_TOKEN_#2'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client2'),
                'parameters' => DataBag::createFromArray([]),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => [],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => null,
            ],
            [
                'id' => AccessTokenId::create('VALID_ACCESS_TOKEN_FOR_USERINFO'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client1'),
                'parameters' => DataBag::createFromArray(['token_type' => 'Bearer']),
                'metadatas' => DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
                'scope' => ['openid', 'profile', 'email', 'phone', 'address'],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => null,
            ],
            [
                'id' => AccessTokenId::create('VALID_ACCESS_TOKEN_FOR_SIGNED_USERINFO'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client2'),
                'parameters' => DataBag::createFromArray(['token_type' => 'Bearer']),
                'metadatas' => DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
                'scope' => ['openid', 'profile', 'email', 'phone', 'address'],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => null,
            ],
            [
                'id' => AccessTokenId::create('INVALID_ACCESS_TOKEN_FOR_USERINFO'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client2'),
                'parameters' => DataBag::createFromArray(['token_type' => 'Bearer']),
                'metadatas' => DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
                'scope' => [],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => null,
            ],
            [
                'id' => AccessTokenId::create('ACCESS_TOKEN_ISSUED_THROUGH_TOKEN_ENDPOINT'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client2'),
                'parameters' => DataBag::createFromArray(['token_type' => 'Bearer']),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => ['openid', 'profile', 'email', 'phone', 'address'],
                'expires_at' => new \DateTimeImmutable('now +3600 seconds'),
                'refresh_token' => null,
                'resource_server_id' => null,
            ],
        ];
    }

    private function loadAuthorizationCodes()
    {
        $manager = $this->getContainer()->get(AuthCodeRepository::class);

        foreach ($this->getAuthCodes() as $authCodeInformation) {
            $authCode = AuthCode::createEmpty();
            $authCode = $authCode->create(
                $authCodeInformation['id'],
                $authCodeInformation['client_id'],
                $authCodeInformation['user_account_id'],
                $authCodeInformation['query_parameters'],
                $authCodeInformation['redirect_uri'],
                $authCodeInformation['expires_at'],
                $authCodeInformation['parameters'],
                $authCodeInformation['metadatas'],
                $authCodeInformation['scope'],
                $authCodeInformation['with_refresh_token'],
                null
            );
            if ($authCodeInformation['is_used']) {
                $authCode = $authCode->markAsUsed();
            }
            if ($authCodeInformation['is_revoked']) {
                $authCode = $authCode->markAsRevoked();
            }
            $manager->save($authCode);
        }
    }

    /**
     * @return array
     */
    private function getAuthCodes(): array
    {
        return [
            [
                'id' => AuthCodeId::create('VALID_AUTH_CODE'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'id' => AuthCodeId::create('EXPIRED_AUTH_CODE'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now -1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'id' => AuthCodeId::create('AUTH_CODE_WITH_CODE_VERIFIER_PLAIN'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [
                    'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                    'code_challenge_method' => 'plain',
                ],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'id' => AuthCodeId::create('AUTH_CODE_WITH_CODE_VERIFIER_S256'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [
                    'code_challenge' => 'DSmbHrVIcI0EU05-BQxCe1bt-hXRNjejSEvdYbq_g4Q',
                    'code_challenge_method' => 'S256',
                ],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'id' => AuthCodeId::create('AUTH_CODE_REVOKED'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [
                    'code_challenge' => 'DSmbHrVIcI0EU05-BQxCe1bt-hXRNjejSEvdYbq_g4Q',
                    'code_challenge_method' => 'S256',
                ],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => true,
                'is_used' => false,
            ],
            [
                'id' => AuthCodeId::create('AUTH_CODE_USED'),
                'client_id' => ClientId::create('client1'),
                'user_account_id' => UserAccountId::create('john.1'),
                'query_parameters' => [
                    'code_challenge' => 'DSmbHrVIcI0EU05-BQxCe1bt-hXRNjejSEvdYbq_g4Q',
                    'code_challenge_method' => 'S256',
                ],
                'redirect_uri' => 'https://www.example.com/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameters' => new DataBag(),
                'metadatas' => new DataBag(),
                'scope' => ['openid', 'email', 'phone', 'address'],
                'with_refresh_token' => false,
                'is_revoked' => false,
                'is_used' => true,
            ],
        ];
    }

    private function loadRefreshTokens()
    {
        $manager = $this->getContainer()->get(RefreshTokenRepositoryInterface::class);

        foreach ($this->getRefreshTokens() as $refreshTokenInformation) {
            $refreshToken = RefreshToken::createEmpty();
            $refreshToken = $refreshToken->create(
                $refreshTokenInformation['id'],
                $refreshTokenInformation['resource_owner_id'],
                $refreshTokenInformation['client_id'],
                $refreshTokenInformation['parameters'],
                $refreshTokenInformation['metadatas'],
                $refreshTokenInformation['scope'],
                $refreshTokenInformation['expires_at'],
                $refreshTokenInformation['resource_server_id']
            );
            if ($refreshTokenInformation['is_revoked']) {
                $refreshToken = $refreshToken->markAsRevoked();
            }
            $manager->save($refreshToken);
        }
    }

    /**
     * @return array
     */
    private function getRefreshTokens(): array
    {
        return [
            [
                'id' => RefreshTokenId::create('EXPIRED_REFRESH_TOKEN'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client1'),
                'parameters' => DataBag::createFromArray([]),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => [],
                'resource_server_id' => null,
                'expires_at' => new \DateTimeImmutable('now -2 days'),
                'is_revoked' => false,
            ],
            [
                'id' => RefreshTokenId::create('VALID_REFRESH_TOKEN'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client1'),
                'parameters' => DataBag::createFromArray([]),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => [],
                'resource_server_id' => null,
                'expires_at' => new \DateTimeImmutable('now +2 days'),
                'is_revoked' => false,
            ],
            [
                'id' => RefreshTokenId::create('REVOKED_REFRESH_TOKEN'),
                'resource_owner_id' => UserAccountId::create('john.1'),
                'client_id' => ClientId::create('client1'),
                'parameters' => DataBag::createFromArray([]),
                'metadatas' => DataBag::createFromArray([]),
                'scope' => [],
                'resource_server_id' => null,
                'expires_at' => new \DateTimeImmutable('now +2 days'),
                'is_revoked' => true,
            ],
        ];
    }

    private function loadPreConfiguredAuthorizations()
    {
        $manager = $this->getContainer()->get(PreConfiguredAuthorizationRepository::class);

        foreach ($this->getPreConfiguredAuthorizations() as $preConfiguredAuthorizationInformation) {
            $preConfiguredAuthorization = $manager->create(
                $preConfiguredAuthorizationInformation['user-account-id'],
                $preConfiguredAuthorizationInformation['client-id'],
                $preConfiguredAuthorizationInformation['scope'],
                $preConfiguredAuthorizationInformation['resource-server-id']
            );
            $manager->save($preConfiguredAuthorization);
        }
    }

    /**
     * @return array
     */
    private function getPreConfiguredAuthorizations(): array
    {
        return [
            [
                'user-account-id' => UserAccountId::create('john.1'),
                'client-id' => ClientId::create('client1'),
                'scope' => ['openid', 'profile', 'phone', 'address', 'email'],
                'resource-server-id' => null,
            ],
        ];
    }
}
