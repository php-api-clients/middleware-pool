<?php declare(strict_types=1);

namespace ApiClients\Middleware\Pool;

use ApiClients\Foundation\Middleware\Annotation\First;
use ApiClients\Foundation\Middleware\Annotation\Last;
use ApiClients\Foundation\Middleware\ErrorTrait;
use ApiClients\Foundation\Middleware\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\CancellablePromiseInterface;
use function React\Promise\reject;
use ResourcePool\Allocation;
use ResourcePool\Pool;
use function React\Promise\resolve;
use Throwable;

class PoolMiddleware implements MiddlewareInterface
{
    /**
     * @var Allocation[]
     */
    private $allocations;

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return CancellablePromiseInterface
     *
     * @First()
     */
    public function pre(
        RequestInterface $request,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if (!isset($options[self::class][Options::POOL])) {
            return resolve($request);
        }

        /** @var Pool $pool */
        $pool = $options[self::class][Options::POOL];
        return $pool->allocateOne()->then(function (Allocation $allocation) use ($request, $transactionId){
            $this->allocations[$transactionId] = $allocation;
            return resolve($request);
        });
    }

    /**
     * @param ResponseInterface $response
     * @param array $options
     * @return CancellablePromiseInterface
     *
     * @Last()
     */
    public function post(
        ResponseInterface $response,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if ($this->allocations[$transactionId] instanceof Allocation) {
            $this->allocations[$transactionId]->releaseOne();
        }

        return resolve($response);
    }

    public function error(
        Throwable $throwable,
        string $transactionId,
        array $options = []
    ): CancellablePromiseInterface {
        if ($this->allocations[$transactionId] instanceof Allocation) {
            $this->allocations[$transactionId]->releaseOne();
        }

        return reject($throwable);
    }


}
