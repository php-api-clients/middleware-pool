<?php declare(strict_types=1);

namespace ApiClients\Foundation\Pool;

use ApiClients\Tools\Psr7\Oauth1\Definition\AccessToken;
use ApiClients\Tools\Psr7\Oauth1\Definition\ConsumerKey;
use ApiClients\Tools\Psr7\Oauth1\Definition\ConsumerSecret;
use ApiClients\Tools\Psr7\Oauth1\Definition\TokenSecret;
use ResourcePool\Pool;

final class Options
{
    const POOL = Pool::class;
}
