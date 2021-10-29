<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use Closure;
use const FILTER_VALIDATE_URL;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class CommonParametersRule extends AbstractInternationalizedRule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        foreach ($this->getSupportedParameters() as $parameter => $closure) {
            $id = $this->getInternationalizedParameters($commandParameters, $parameter, $closure);
            foreach ($id as $k => $v) {
                $validatedParameters->set($k, $v);
            }
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    private function getSupportedParameters(): array
    {
        return [
            'client_name' => static function (): void {
            },
            'client_uri' => $this->getUriVerificationClosure(),
            'logo_uri' => $this->getUriVerificationClosure(),
            'tos_uri' => $this->getUriVerificationClosure(),
            'policy_uri' => $this->getUriVerificationClosure(),
        ];
    }

    private function getUriVerificationClosure(): Closure
    {
        return static function ($k, $v): void {
            if (filter_var($v, FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException(sprintf('The parameter with key "%s" is not a valid URL.', $k));
            }
        };
    }
}
