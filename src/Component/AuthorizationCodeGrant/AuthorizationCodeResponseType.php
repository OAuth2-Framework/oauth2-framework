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
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

class AuthorizationCodeResponseType implements ResponseType
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
     * @var AuthorizationCodeIdGenerator
     */
    private $authorizationCodeIdGenerator;

    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    /**
     * @var PKCEMethodManager
     */
    private $pkceMethodManager;

    /**
     * AuthorizationCodeResponseType constructor.
     *
     * @param AuthorizationCodeIdGenerator $authorizationCodeIdGenerator
     * @param AuthorizationCodeRepository  $authorizationCodeRepository
     * @param int                          $authorizationCodeLifetime
     * @param PKCEMethodManager            $pkceMethodManager
     * @param bool                         $pkceForPublicClientsEnforced
     */
    public function __construct(AuthorizationCodeIdGenerator $authorizationCodeIdGenerator, AuthorizationCodeRepository $authorizationCodeRepository, int $authorizationCodeLifetime, PKCEMethodManager $pkceMethodManager, bool $pkceForPublicClientsEnforced)
    {
        $this->authorizationCodeIdGenerator = $authorizationCodeIdGenerator;
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->authorizationCodeLifetime = $authorizationCodeLifetime;
        $this->pkceMethodManager = $pkceMethodManager;
        $this->pkceForPublicClientsEnforced = $pkceForPublicClientsEnforced;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedGrantTypes(): array
    {
        return ['authorization_code'];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'code';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization): Authorization
    {
        $queryParams = $authorization->getQueryParams();

        if (!array_key_exists('code_challenge', $queryParams)) {
            if (true === $this->pkceForPublicClientsEnforced && $authorization->getClient()->isPublic()) {
                throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'Non-confidential clients must set a proof key (PKCE) for code exchange.');
            }
        } else {
            $codeChallengeMethod = array_key_exists('code_challenge_method', $queryParams) ? $queryParams['code_challenge_method'] : 'plain';
            if (!$this->pkceMethodManager->has($codeChallengeMethod)) {
                throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, sprintf('The challenge method "%s" is not supported.', $codeChallengeMethod));
            }
        }

        $authorizationCodeId = $this->authorizationCodeIdGenerator->createAuthorizationCodeId();
        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            $authorizationCodeId,
            $authorization->getClient()->getPublicId(),
            $authorization->getUserAccount()->getPublicId(),
            $queryParams,
            $authorization->getRedirectUri(),
            (new \DateTimeImmutable())->setTimestamp(time() + $this->authorizationCodeLifetime),
            DataBag::create([]),
            DataBag::create([]),
            $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $authorization = $authorization->withResponseParameter('code', $authorizationCodeId->getValue());

        return $authorization;
    }
}
