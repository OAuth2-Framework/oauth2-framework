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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use function Safe\sprintf;

final class AuthorizationCodeResponseType implements ResponseType
{
    private int $authorizationCodeLifetime;

    private bool $pkceForPublicClientsEnforced;

    private AuthorizationCodeRepository $authorizationCodeRepository;

    private PKCEMethodManager $pkceMethodManager;

    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, int $authorizationCodeLifetime, PKCEMethodManager $pkceMethodManager, bool $pkceForPublicClientsEnforced)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->authorizationCodeLifetime = $authorizationCodeLifetime;
        $this->pkceMethodManager = $pkceMethodManager;
        $this->pkceForPublicClientsEnforced = $pkceForPublicClientsEnforced;
    }

    public function associatedGrantTypes(): array
    {
        return ['authorization_code'];
    }

    public function name(): string
    {
        return 'code';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    public function preProcess(AuthorizationRequest $authorization): void
    {
        $queryParams = $authorization->getQueryParams();

        if (!\array_key_exists('code_challenge', $queryParams)) {
            if (true === $this->pkceForPublicClientsEnforced && $authorization->getClient()->isPublic()) {
                throw OAuth2Error::invalidRequest('Non-confidential clients must set a proof key (PKCE) for code exchange.');
            }
        } else {
            $codeChallengeMethod = \array_key_exists('code_challenge_method', $queryParams) ? $queryParams['code_challenge_method'] : 'plain';
            if (!$this->pkceMethodManager->has($codeChallengeMethod)) {
                throw OAuth2Error::invalidRequest(sprintf('The challenge method "%s" is not supported.', $codeChallengeMethod));
            }
        }

        $authorizationCode = $this->authorizationCodeRepository->create(
            $authorization->getClient()->getClientId(),
            $authorization->getUserAccount()->getUserAccountId(),
            $authorization->getQueryParams(),
            $authorization->getRedirectUri(),
            (new \DateTimeImmutable())->setTimestamp(time() + $this->authorizationCodeLifetime),
            new DataBag([]),
            $authorization->getMetadata(),
            null !== $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $authorization->setResponseParameter('code', $authorizationCode->getId()->getValue());
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        //Nothing to do
    }
}
