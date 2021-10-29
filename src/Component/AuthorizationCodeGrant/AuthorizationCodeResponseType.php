<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use function array_key_exists;
use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenType;

final class AuthorizationCodeResponseType implements ResponseType
{
    public function __construct(
        private AuthorizationCodeRepository $authorizationCodeRepository,
        private int $authorizationCodeLifetime,
        private PKCEMethodManager $pkceMethodManager,
        private bool $pkceForPublicClientsEnforced
    ) {
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

        if (! array_key_exists('code_challenge', $queryParams)) {
            if ($this->pkceForPublicClientsEnforced === true && $authorization->getClient()->isPublic()) {
                throw OAuth2Error::invalidRequest(
                    'Non-confidential clients must set a proof key (PKCE) for code exchange.'
                );
            }
        } else {
            $codeChallengeMethod = array_key_exists(
                'code_challenge_method',
                $queryParams
            ) ? $queryParams['code_challenge_method'] : 'plain';
            if (! $this->pkceMethodManager->has($codeChallengeMethod)) {
                throw OAuth2Error::invalidRequest(
                    sprintf('The challenge method "%s" is not supported.', $codeChallengeMethod)
                );
            }
        }

        $authorizationCode = $this->authorizationCodeRepository->create(
            $authorization->getClient()
                ->getClientId(),
            $authorization->getUserAccount()
                ->getUserAccountId(),
            $authorization->getQueryParams(),
            $authorization->getRedirectUri(),
            (new DateTimeImmutable())->setTimestamp(time() + $this->authorizationCodeLifetime),
            new DataBag([]),
            $authorization->getMetadata(),
            $authorization->getResourceServer()?->getResourceServerId()
        );
        $this->authorizationCodeRepository->save($authorizationCode);
        $authorization->setResponseParameter('code', $authorizationCode->getId()->getValue());
    }

    public function process(AuthorizationRequest $authorization, TokenType $tokenType): void
    {
        //Nothing to do
    }
}
