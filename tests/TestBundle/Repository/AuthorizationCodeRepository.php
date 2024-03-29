<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode as AuthorizationCodeInterface;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository as AuthorizationCodeRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\AuthorizationCode;

final class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface
{
    /**
     * @var array<string, AuthorizationCodeInterface>
     */
    private array $authorizationCodes = [];

    public function __construct()
    {
        $this->load();
    }

    public function find(AuthorizationCodeId $authorizationCodeId): ?AuthorizationCodeInterface
    {
        return $this->authorizationCodes[$authorizationCodeId->getValue()] ?? null;
    }

    public function save(AuthorizationCodeInterface $authorizationCode): void
    {
        $this->authorizationCodes[$authorizationCode->getId()->getValue()] = $authorizationCode;
    }

    public function create(
        ClientId $clientId,
        UserAccountId $userAccountId,
        array $queryParameters,
        string $redirectUri,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): AuthorizationCodeInterface {
        return new AuthorizationCode(
            AuthorizationCodeId::create(bin2hex(random_bytes(32))),
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }

    private function load(): void
    {
        foreach ($this->getData() as $datum) {
            $authorizationCode = new AuthorizationCode(
                AuthorizationCodeId::create($datum['authorization_code_id']),
                new ClientId($datum['client_id']),
                new UserAccountId($datum['user_account_id']),
                $datum['query_parameters'],
                $datum['redirect_uri'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $authorizationCode->markAsRevoked();
            }
            if ($datum['is_used']) {
                $authorizationCode->markAsUsed();
            }
            $this->save($authorizationCode);
        }
    }

    private function getData(): array
    {
        return [
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT',
                'client_id' => 'CLIENT_ID_5',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'REVOKED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'EXPIRED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'USED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => true,
            ],
        ];
    }
}
