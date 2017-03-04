<?php declare(strict_types=1);

namespace ApiClients\Middleware\Pool;

use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use ApiClients\Foundation\Middleware\Priority;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\CancellablePromiseInterface;
use ResourcePool\Allocation;
use ResourcePool\Pool;
use function React\Promise\resolve;

class PoolMiddleware implements MiddlewareInterface
{
    use ErrorTrait;

    /**
     * @var Allocation
     */
    private $allocation;

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return CancellablePromiseInterface
     */
    public function pre(RequestInterface $request, array $options = []): CancellablePromiseInterface
    {
        if (!isset($options[self::class][Options::POOL])) {
            return resolve($request);
        }

        /** @var Pool $pool */
        $pool = $options[self::class][Options::POOL];
        return $pool->allocateOne()->then(function (Allocation $allocation) use ($request) {
            $this->allocation = $allocation;
            return resolve($request);
        });
    }

    /**
     * @param ResponseInterface $response
     * @param array $options
     * @return CancellablePromiseInterface
     */
    public function post(ResponseInterface $response, array $options = []): CancellablePromiseInterface
    {
        if ($this->allocation instanceof Allocation) {
            $this->allocation->releaseOne();
        }

        return resolve($response);
    }

    /**
     * @return int
     */
    public function priority(): int
    {
        return Priority::FIRST;
    }
}
