<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message;

use function array_key_exists;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Message\Factory\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuth2MessageFactoryManager
{
    /**
     * @var MessageExtension[]
     */
    private array $extensions = [];

    /**
     * @var ResponseFactory[]
     */
    private array $responseFactories = [];

    public function __construct(
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function addFactory(ResponseFactory $responseFactory): self
    {
        $this->responseFactories[$responseFactory->getSupportedCode()] = $responseFactory;

        return $this;
    }

    public function addExtension(MessageExtension $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function getResponse(OAuth2Error $message, array $additionalData = []): ResponseInterface
    {
        $code = $message->getCode();
        $data = array_merge($additionalData, $message->getData());
        foreach ($this->extensions as $extension) {
            $data += $extension->process($message);
        }

        $factory = $this->getFactory($code);
        $response = $this->responseFactory->createResponse($code);

        return $factory->createResponse($data, $response);
    }

    private function getFactory(int $code): ResponseFactory
    {
        if (! array_key_exists($code, $this->responseFactories)) {
            throw new InvalidArgumentException(sprintf('The response code "%d" is not supported', $code));
        }

        return $this->responseFactories[$code];
    }
}
