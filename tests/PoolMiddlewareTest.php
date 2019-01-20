<?php declare(strict_types=1);

namespace ApiClients\Tests\Middleware\Pool;

use ApiClients\Middleware\Pool\PoolMiddleware;
use ApiClients\Tools\TestUtilities\TestCase;
use Psr\Http\Message\RequestInterface;
use ResourcePool\Allocation;
use ResourcePool\Pool;

/**
 * @internal
 */
class PoolMiddlewareTest extends TestCase
{
    public function testRequest(): void
    {
        $request = $this->prophesize(RequestInterface::class);

        /** @var Allocation $allocation */
        $allocation = null;
        $pool = new Pool(1);
        $options = [
            PoolMiddleware::class => [
                Pool::class => $pool,
            ],
        ];
        $middleware = new PoolMiddleware();
        $pool->allocateOne()->then(function (Allocation $passedAllocation) use (&$allocation): void {
            $allocation = $passedAllocation;
        });

        $preCalled = false;
        $middleware->pre($request->reveal(), 'abc', $options)->then(function () use (&$preCalled): void {
            $preCalled = true;
        });

        self::assertFalse($preCalled);

        $allocation->releaseOne();

        self::assertTrue($preCalled);
    }

    public function testRequestErrored(): void
    {
        $request = $this->prophesize(RequestInterface::class);

        $pool = new Pool(1);
        $options = [
            PoolMiddleware::class => [
                Pool::class => $pool,
            ],
        ];
        $middleware = new PoolMiddleware();

        self::assertSame(0, $pool->getUsage());
        $middleware->pre($request->reveal(), 'abc', $options);
        self::assertSame(1, $pool->getUsage());
        $middleware->error(new \Exception(), 'abc', $options);
        self::assertSame(0, $pool->getUsage());
    }

    public function testRequestNoPool(): void
    {
        $request = $this->prophesize(RequestInterface::class);

        /** @var Allocation $allocation */
        $options = [];
        $middleware = new PoolMiddleware();
        $preCalled = false;
        $middleware->pre($request->reveal(), 'abc', $options)->then(function () use (&$preCalled): void {
            $preCalled = true;
        });

        self::assertTrue($preCalled);
    }
}
