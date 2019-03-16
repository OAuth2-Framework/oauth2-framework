<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
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

final class AuthorizationCodeResponseType implements ResponseType
{
    /**
     * @var int
     */
    private $authorizationCodeLifetime;

    /**
     * @var bool
     */
    private $pkceForPublicClientsEnforced;

    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    /**
     * @var PKCEMethodManager
     */
    private $pkceMethodManager;

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
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, 'Non-confidential clients must set a proof key (PKCE) for code exchange.');
            }
        } else {
            $codeChallengeMethod = \array_key_exists('code_challenge_method', $queryParams) ? $queryParams['code_challenge_method'] : 'plain';
            if (!$this->pkceMethodManager->has($codeChallengeMethod)) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, \Safe\sprintf('The challenge method "%s" is not supported.', $codeChallengeMethod));
            }
        }

        $authorizationCode = $this->authorizationCodeRepository->create(
            $authorization->getClient()->getClientId(),
            $authorization->getUserAccount()->getUserAccountId(),
            $authorization->getQueryParams(),
            $authorization->getRedirectUri(),
            (new \DateTimeImmutable())->setTimestamp(\time() + $this->authorizationCodeLifetime),
            new DataBag([]),
            $authorization->getMetadata(),
            $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $authorization->setResponseParameter('code', $authorizationCode->getId()->getValue());
    }

    public function process(AuthorizationRequest $authorization): void
    {
        //Nothing to do
    }
}
