<?php

namespace Asaas\Http;

class Request
{

    protected $deferred;

    protected $method;

    protected $env;
    protected $url;

    protected $content;

    protected $headers;

    public function __construct(Deferred $deferred, string $method, bool $production, Endpoint $url, string $content, array $headers = [])
    {
        $this->deferred = $deferred;
        $this->method = $method;
        $this->env = $production? "www" : "sandbox";
        $this->url = $url;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return 'https://'. $this->env . Http::BASE_URL.'/'.$this->url;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getDeferred(): Deferred
    {
        return $this->deferred;
    }

    public function getBucketID(): string
    {
        return $this->method.$this->url->toAbsoluteEndpoint(true);
    }

    public function __toString()
    {
        return 'REQ '.strtoupper($this->method).' '.$this->url;
    }
}