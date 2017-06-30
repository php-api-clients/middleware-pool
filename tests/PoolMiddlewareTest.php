<?php

namespace ApiClients\Tests\Middleware\Pool;

use ApiClients\Middleware\Pool\PoolMiddleware;
use ApiClients\Tools\TestUtilities\TestCase;
use Psr\Http\Message\RequestInterface;
use ResourcePool\Allocation;
use ResourcePool\Pool;

class PoolMiddlewareTest extends TestCase
{
    public function testRequest()
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
        $pool->allocateOne()->then(function (Allocation $passedAllocation) use (&$allocation) {
            $allocation = $passedAllocation;
        });

        $preCalled = false;
        $middleware->pre($request->reveal(), 'abc', $options)->then(function () use (&$preCalled) {
            $preCalled = true;
        });

        self::assertFalse($preCalled);

        $allocation->releaseOne();

        self::assertTrue($preCalled);
    }

    public function testRequestNoPool()
    {
        $request = $this->prophesize(RequestInterface::class);

        /** @var Allocation $allocation */
        $options = [];
        $middleware = new PoolMiddleware();
        $preCalled = false;
        $middleware->pre($request->reveal(), 'abc', $options)->then(function () use (&$preCalled) {
            $preCalled = true;
        });

        self::assertTrue($preCalled);
    }
}
