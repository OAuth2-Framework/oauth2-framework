<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AbstractAuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCode extends AbstractAuthorizationCode
{
    public function __construct(
        private readonly AuthorizationCodeId $authorizationCodeId,
        ClientId $clientId,
        UserAccountId $userAccountId,
        array $queryParameters,
        string $redirectUri,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ) {
        parent::__construct($clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameter, $metadata, $resourceServerId);
    }

    public static function create(
        AuthorizationCodeId $authorizationCodeId,
        ClientId $clientId,
        UserAccountId $userAccountId,
        array $queryParameters,
        string $redirectUri,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): self {
        return new self(
            $authorizationCodeId,
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

    public function getId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }
}
