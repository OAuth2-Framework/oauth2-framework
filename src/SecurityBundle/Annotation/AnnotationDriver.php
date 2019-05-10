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

namespace OAuth2Framework\SecurityBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Psr\Http\Message\ResponseInterface;
use ReflectionObject;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class AnnotationDriver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Checker[]
     */
    private $checkers = [];

    /**
     * @var OAuth2MessageFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    public function __construct(Reader $reader, TokenStorageInterface $tokenStorage, OAuth2MessageFactoryManager $oauth2ResponseFactoryManager)
    {
        $this->reader = $reader;
        $this->tokenStorage = $tokenStorage;
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    public function add(Checker $checker): void
    {
        $this->checkers[] = $checker;
    }

    /**
     * @return Checker[]
     */
    public function all(): array
    {
        return $this->checkers;
    }

    public function onKernelController(FilterControllerEvent $event): void
    {
        $controller = $event->getController();
        if (!\is_array($controller)) {
            return;
        }

        $object = new ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);
        $classConfigurations = $this->reader->getClassAnnotations($object);
        $methodConfigurations = $this->reader->getMethodAnnotations($method);

        foreach (\array_merge($classConfigurations, $methodConfigurations) as $configuration) {
            if ($configuration instanceof OAuth2) {
                $this->processOAuth2Annotation($event, $configuration);
            }
        }
    }

    private function processOAuth2Annotation(FilterControllerEvent $event, OAuth2 $configuration): void
    {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof OAuth2Token) {
            $this->createAuthenticationException($event, 'OAuth2 authentication required', $configuration);

            return;
        }

        foreach ($this->all() as $checker) {
            try {
                $checker->check($token, $configuration);
            } catch (Throwable $e) {
                $this->createAccessDeniedException($event, $e->getMessage(), $configuration, $e);
            }
        }
    }

    private function createAuthenticationException(FilterControllerEvent $event, string $message, OAuth2 $configuration): void
    {
        $additionalData = null !== $configuration->getScope() ? ['scope' => $configuration->getScope()] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            OAuth2Error::accessDenied($message),
            $additionalData
        );

        $this->updateFilterControllerEvent($event, $response);
    }

    private function createAccessDeniedException(FilterControllerEvent $event, string $message, OAuth2 $configuration, Throwable $previous): void
    {
        $additionalData = null !== $configuration->getScope() ? ['scope' => $configuration->getScope()] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            new OAuth2Error(
            403,
            OAuth2Error::ERROR_ACCESS_DENIED,
                $message,
                [],
                $previous
            ),
            $additionalData
        );
        $this->updateFilterControllerEvent($event, $response);
    }

    private function updateFilterControllerEvent(FilterControllerEvent $event, ResponseInterface $psr7Response): void
    {
        $event->setController(static function () use ($psr7Response): Response {
            $factory = new HttpFoundationFactory();

            return $factory->createResponse($psr7Response);
        });
    }
}
