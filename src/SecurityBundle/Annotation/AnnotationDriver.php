<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Annotation;

use function is_array;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use OAuth2Framework\SecurityBundle\Security\Authentication\OAuth2Token;
use Psr\Http\Message\ResponseInterface;
use ReflectionObject;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

final class AnnotationDriver
{
    /**
     * @var Checker[]
     */
    private array $checkers = [];

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
    ) {
    }

    public function add(Checker $checker): self
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * @return Checker[]
     */
    public function all(): array
    {
        return $this->checkers;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (! is_array($controller)) {
            return;
        }

        $object = new ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);

        $attributes = array_merge($object->getAttributes(OAuth2::class), $method->getAttributes(OAuth2::class));

        foreach ($attributes as $attribute) {
            $annotation = $attribute->newInstance();
            $this->processOAuth2Annotation($event, $annotation);
        }
    }

    private function processOAuth2Annotation(ControllerEvent $event, OAuth2 $configuration): void
    {
        $token = $this->tokenStorage->getToken();

        if (! $token instanceof OAuth2Token) {
            $this->createAuthenticationException($event, $configuration);

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

    private function createAuthenticationException(ControllerEvent $event, OAuth2 $configuration): void
    {
        $additionalData = $configuration->getScope() !== null ? [
            'scope' => $configuration->getScope(),
        ] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            OAuth2Error::accessDenied('OAuth2 authentication required'),
            $additionalData
        );

        $this->updateControllerEvent($event, $response);
    }

    private function createAccessDeniedException(
        ControllerEvent $event,
        string $message,
        OAuth2 $configuration,
        Throwable $previous
    ): void {
        $additionalData = $configuration->getScope() !== null ? [
            'scope' => $configuration->getScope(),
        ] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            new OAuth2Error(403, OAuth2Error::ERROR_ACCESS_DENIED, $message, [], $previous),
            $additionalData
        );
        $this->updateControllerEvent($event, $response);
    }

    private function updateControllerEvent(ControllerEvent $event, ResponseInterface $psr7Response): void
    {
        $event->setController(static function () use ($psr7Response): Response {
            $factory = new HttpFoundationFactory();

            return $factory->createResponse($psr7Response);
        });
    }
}
