<?php

declare(strict_types=1);

return [
    'cache_ttl' => (int) env('PROFILE_CACHE_TTL', 60 * 60 * 10),
];
