<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\User\MaxAgeParameterAuthenticationChecker;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class MaxAgeParameterCheckerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function theUserHasNeverBeenConnected(): void
    {
        $client = $this->prophesize(Client::class);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(true)
        ;
        $authorization->getQueryParam('max_age')
            ->willReturn(3600)
        ;
        $authorization->hasUserAccount()
            ->willReturn(false)
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }

    /**
     * @test
     */
    public function thereIsNoMaxAgeConstraintThenTheCheckSucceeded(): void
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(false)
        ;

        $userAccount = $this->prophesize(UserAccount::class);

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(false)
        ;
        $authorization->hasUserAccount()
            ->willReturn(true)
        ;
        $authorization->getUserAccount()
            ->willReturn($userAccount->reveal())
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsConstraintFromTheClientThatIsSatisfied(): void
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(true)
        ;
        $client->get('default_max_age')
            ->willReturn(3600)
        ;

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()
            ->willReturn(time() - 100)
        ;

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(false)
        ;
        $authorization->hasUserAccount()
            ->willReturn(true)
        ;
        $authorization->getUserAccount()
            ->willReturn($userAccount->reveal())
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsConstraintFromTheAuthorizationThatIsSatisfied(): void
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(false)
        ;

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()
            ->willReturn(time() - 100)
        ;

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(true)
        ;
        $authorization->getQueryParam('max_age')
            ->willReturn(3600)
        ;
        $authorization->hasUserAccount()
            ->willReturn(true)
        ;
        $authorization->getUserAccount()
            ->willReturn($userAccount->reveal())
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        $checker->isAuthenticationNeeded($authorization->reveal(), $userAccount->reveal(), false);
        static::assertTrue(true);
    }

    /**
     * @test
     */
    public function thereIsAConstraintButTheUserNeverLoggedIn(): void
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(false)
        ;

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()
            ->willReturn(null)
        ;

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(true)
        ;
        $authorization->getQueryParam('max_age')
            ->willReturn(3600)
        ;
        $authorization->hasUserAccount()
            ->willReturn(true)
        ;
        $authorization->getUserAccount()
            ->willReturn($userAccount->reveal())
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }

    /**
     * @test
     */
    public function thereIsAConstraintThatIsNotSatisfied(): void
    {
        $client = $this->prophesize(Client::class);
        $client->has('default_max_age')
            ->willReturn(false)
        ;

        $userAccount = $this->prophesize(UserAccount::class);
        $userAccount->getLastLoginAt()
            ->willReturn(time() - 10000)
        ;

        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->hasQueryParam('max_age')
            ->willReturn(true)
        ;
        $authorization->getQueryParam('max_age')
            ->willReturn(3600)
        ;
        $authorization->hasUserAccount()
            ->willReturn(true)
        ;
        $authorization->getUserAccount()
            ->willReturn($userAccount->reveal())
        ;
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $checker = new MaxAgeParameterAuthenticationChecker();

        static::assertTrue($checker->isAuthenticationNeeded($authorization->reveal()));
    }
}
