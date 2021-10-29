<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRule;

use function call_user_func;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class RuleHandler
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        return call_user_func($this->callback, $clientId, $commandParameters, $validatedParameters);
    }
}
