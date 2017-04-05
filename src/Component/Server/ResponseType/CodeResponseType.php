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

namespace OAuth2Framework\Component\Server\ResponseType;

use OAuth2Framework\Component\Server\Command\AuthCode\CreateAuthCodeCommand;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use SimpleBus\Message\Bus\MessageBus;

final class CodeResponseType implements ResponseTypeInterface
{
    /**
     * @var bool
     */
    private $pkceForPublicClientsEnforced;

    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * @var PKCEMethodManager
     */
    private $PKCEMethodManager;

    /**
     * CodeResponseType constructor.
     *
     * @param MessageBus        $commandBus
     * @param PKCEMethodManager $PKCEMethodManager
     * @param bool              $pkceForPublicClientsEnforced
     */
    public function __construct(MessageBus $commandBus, PKCEMethodManager $PKCEMethodManager, bool $pkceForPublicClientsEnforced)
    {
        $this->commandBus = $commandBus;
        $this->PKCEMethodManager = $PKCEMethodManager;
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
        $offlineAccess = $authorization->hasOfflineAccess();
        $queryParams = $authorization->getQueryParams();

        if (!array_key_exists('code_challenge', $queryParams)) {
            if (true === $this->isPKCEForPublicClientsEnforced() && $authorization->getClient()->isPublic()) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Non-confidential clients must set a proof key (PKCE) for code exchange.']);
            }
        } else {
            $codeChallengeMethod = array_key_exists('code_challenge_method', $queryParams) ? $queryParams['code_challenge_method'] : 'plain';
            if (!$this->PKCEMethodManager->has($codeChallengeMethod)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => sprintf('The challenge method \'%s\' is not supported.', $codeChallengeMethod)]);
            }
        }

        $dataTransporter = new DataTransporter();
        $command = CreateAuthCodeCommand::create(
            $authorization->getClient()->getPublicId(),
            $authorization->getUserAccount()->getPublicId(),
            $queryParams,
            $authorization->getRedirectUri(),
            new DataBag(), //$parameters,
            new DataBag(), //$metadatas,
            $authorization->getScopes(),
            $offlineAccess,
            null,
            $authorization->getResourceServer() ? $authorization->getResourceServer()->getResourceServerId() : null,
            $dataTransporter
        );
        $this->commandBus->handle($command);

        $authorization = $authorization->withResponseParameter('code', $dataTransporter->getData()->getTokenId()->getValue());

        return $next($authorization);
    }

    /**
     * @return bool
     */
    private function isPKCEForPublicClientsEnforced(): bool
    {
        return $this->pkceForPublicClientsEnforced;
    }
}
