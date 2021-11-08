<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\AbstractClient;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class Client extends AbstractClient
{
    public function __construct(
        protected ClientId $clientId,
        DataBag $parameters,
        ?UserAccountId $ownerId
    ) {
        parent::__construct($parameters, $ownerId);
    }

    public static function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): self
    {
        return new self($clientId, $parameters, $ownerId);
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }
}
