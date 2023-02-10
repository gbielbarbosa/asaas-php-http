<?php

namespace Asaas\Http;

class RateLimit
{

    protected $retry_after;

    public function __construct(float $retry_after)
    {
        $this->retry_after = $retry_after;
    }

    public function getRetryAfter(): float
    {
        return $this->retry_after;
    }

    public function __toString()
    {
        return 'RATELIMIT Non-global, retry after '.$this->retry_after.' s';
    }
}