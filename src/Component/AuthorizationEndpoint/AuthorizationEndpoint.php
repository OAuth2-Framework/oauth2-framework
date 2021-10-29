<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\AuthorizationEndpointHook;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AuthorizationEndpoint
{
    /**
     * @var AuthorizationEndpointHook[]
     */
    private array $hooks = [];

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private TokenTypeGuesser $tokenTypeGuesser,
        private ResponseTypeGuesser $responseTypeGuesser,
        private ResponseModeGuesser $responseModeGuesser,
        private ?ConsentRepository $consentRepository,
        private ExtensionManager $extensionManager,
        private AuthorizationRequestStorage $authorizationRequestStorage,
        private LoginHandler $loginHandler,
        private ConsentHandler $consentHandler
    ) {
    }

    public function addHook(AuthorizationEndpointHook $hook): void
    {
        $this->hooks[] = $hook;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationRequestId = $this->authorizationRequestStorage->getId($request);
        if (! $this->authorizationRequestStorage->has($authorizationRequestId)) {
            throw OAuth2Error::invalidRequest('Unable to find the authorization request');
        }
        $authorizationRequest = $this->authorizationRequestStorage->get($authorizationRequestId);

        try {
            foreach ($this->hooks as $hook) {
                $response = $hook->handle($request, $authorizationRequestId, $authorizationRequest);
                $this->authorizationRequestStorage->set($authorizationRequestId, $authorizationRequest);
                if ($response !== null) {
                    return $response;
                }
            }
            if ($authorizationRequest->hasUserAccount()) {
                return $this->processWithAuthenticatedUser($request, $authorizationRequestId, $authorizationRequest);
            }

            return $this->loginHandler->handle($request, $authorizationRequestId);
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException(
                $e->getMessage(),
                $e->getErrorDescription(),
                $authorizationRequest,
                $e
            );
        } catch (Throwable $e) {
            throw new OAuth2AuthorizationException(
                OAuth2Error::ERROR_INVALID_REQUEST,
                $e->getMessage(),
                $authorizationRequest,
                $e
            );
        }
    }

    private function processWithAuthenticatedUser(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ResponseInterface {
        if (! $authorizationRequest->hasConsentBeenGiven()) {
            $isConsentNeeded = $this->consentRepository === null || ! $this->consentRepository->hasConsentBeenGiven(
                $authorizationRequest
            );
            if ($isConsentNeeded) {
                return $this->consentHandler->handle($request, $authorizationRequestId);
            }
            $authorizationRequest->allow();
        }
        $this->authorizationRequestStorage->remove($authorizationRequestId);

        return $this->processWithAuthorization($request, $authorizationRequest);
    }

    private function processWithAuthorization(
        ServerRequestInterface $request,
        AuthorizationRequest $authorizationRequest
    ): ResponseInterface {
        $this->extensionManager->process($request, $authorizationRequest);
        if (! $authorizationRequest->isAuthorized()) {
            throw new OAuth2AuthorizationException(
                OAuth2Error::ERROR_ACCESS_DENIED,
                'The resource owner denied access to your client.',
                $authorizationRequest
            );
        }
        $tokenType = $this->tokenTypeGuesser->find($authorizationRequest);
        $responseType = $this->responseTypeGuesser->get($authorizationRequest);
        $responseType->preProcess($authorizationRequest);
        $responseType->process($authorizationRequest, $tokenType);

        $responseMode = $this->responseModeGuesser->get($authorizationRequest, $responseType);

        return $this->buildResponse($authorizationRequest, $responseMode);
    }

    private function buildResponse(AuthorizationRequest $authorization, ResponseMode $responseMode): ResponseInterface
    {
        $response = $responseMode->buildResponse(
            $this->responseFactory->createResponse(),
            $authorization->getRedirectUri(),
            $authorization->getResponseParameters()
        );
        foreach ($authorization->getResponseHeaders() as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
