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

namespace OAuth2Framework\Component\Server\AuthorizationCodeGrant;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;

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

    /**
     * CodeResponseType constructor.
     *
     * @param AuthorizationCodeRepository $authorizationCodeRepository
     * @param int                         $authorizationCodeLifetime
     * @param PKCEMethodManager           $pkceMethodManager
     * @param bool                        $pkceForPublicClientsEnforced
     */
    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, int $authorizationCodeLifetime, PKCEMethodManager $pkceMethodManager, bool $pkceForPublicClientsEnforced)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->authorizationCodeLifetime = $authorizationCodeLifetime;
        $this->pkceMethodManager = $pkceMethodManager;
        $this->pkceForPublicClientsEnforced = $pkceForPublicClientsEnforced;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedGrantTypes(): array
    {
        return ['authorization_code'];
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseType(): string
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
    public function process(Authorization $authorization, callable $next): Authorization
    {
        $queryParams = $authorization->getQueryParams();

        if (!array_key_exists('code_challenge', $queryParams)) {
            if (true === $this->pkceForPublicClientsEnforced && $authorization->getClient()->isPublic()) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'Non-confidential clients must set a proof key (PKCE) for code exchange.');
            }
        } else {
            $codeChallengeMethod = array_key_exists('code_challenge_method', $queryParams) ? $queryParams['code_challenge_method'] : 'plain';
            if (!$this->pkceMethodManager->has($codeChallengeMethod)) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('The challenge method "%s" is not supported.', $codeChallengeMethod));
            }
        }

        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            AuthorizationCodeId::create(Base64Url::encode(random_bytes(64))),
            $authorization->getClient()->getPublicId(),
            $authorization->getUserAccount()->getPublicId(),
            $queryParams,
            $authorization->getRedirectUri(),
            (new \DateTimeImmutable())->setTimestamp(time() + $this->authorizationCodeLifetime),
            DataBag::create([]),
            DataBag::create([]),
            $authorization->getScopes(),
            $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $authorization = $authorization->withResponseParameter('code', $authorizationCode->getTokenId()->getValue());

        return $next($authorization);
    }
}
