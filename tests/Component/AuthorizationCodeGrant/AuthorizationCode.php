<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AbstractAuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class AuthorizationCode extends AbstractAuthorizationCode
{
    public function __construct(
        private AuthorizationCodeId $id,
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

    public function getId(): AuthorizationCodeId
    {
        return $this->id;
    }
}
