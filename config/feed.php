<?php

declare(strict_types=1);

return [
    'ttl' => (int) env('FEED_CACHE_TTL', 900),

    'pool_size' => (int) env('FEED_POOL_SIZE', 200),

    'refill_threshold' => (int) env('FEED_REFILL_THRESHOLD', 10),

    'default_limit' => (int) env('FEED_DEFAULT_LIMIT', 10),
    'max_limit' => (int) env('FEED_MAX_LIMIT', 50),
];
