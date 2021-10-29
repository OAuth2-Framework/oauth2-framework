<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository as ConsentRepositoryInterface;

final class ConsentRepository implements ConsentRepositoryInterface
{
    public function hasConsentBeenGiven(AuthorizationRequest $authorizationRequest): bool
    {
        return $authorizationRequest->getClient()
            ->getClientId()
            ->getValue() === 'CLIENT_ID_2' && $authorizationRequest->getUserAccount()
            ->getPublicId()
            ->getValue() === 'john.1'
            ;
    }
}
