<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Throwable;

class ParameterCheckerManager
{
    /**
     * @var ParameterChecker[]
     */
    private array $parameterCheckers = [];

    public static function create(): static
    {
        return new self();
    }

    public function add(ParameterChecker $parameterChecker): static
    {
        $this->parameterCheckers[] = $parameterChecker;

        return $this;
    }

    public function check(AuthorizationRequest $authorization): void
    {
        foreach ($this->parameterCheckers as $parameterChecker) {
            try {
                $parameterChecker->check($authorization);
            } catch (OAuth2AuthorizationException $e) {
                throw $e;
            } catch (OAuth2Error $e) {
                throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorization, $e);
            } catch (Throwable $e) {
                throw new OAuth2AuthorizationException(
                    OAuth2Error::ERROR_INVALID_REQUEST,
                    $e->getMessage(),
                    $authorization,
                    $e
                );
            }
        }
    }
}
