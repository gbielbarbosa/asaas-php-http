<?php

/*
 * This file is a part of the DiscordPHP-Http project.
 *
 * Copyright (c) 2021-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE file.
 */

namespace Asaas\Http;

use Asaas\Http\Exceptions\ContentTooLongException;
use Asaas\Http\Exceptions\InvalidTokenException;
use Asaas\Http\Exceptions\NoPermissionsException;
use Asaas\Http\Exceptions\NotFoundException;
use Asaas\Http\Exceptions\RequestFailedException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use RuntimeException;
use SplQueue;
use Throwable;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Http
{
    public const VERSION = 'v1.0.0';

    public const HTTP_API_VERSION = 3;

    public const BASE_URL = 'asaas.com/api/v'.self::HTTP_API_VERSION;

    public const CONCURRENT_REQUESTS = 5;

    private $token;
    private $production;

    protected $logger;

    protected $driver;

    protected $loop;

    protected $buckets = [];

    protected $queue;

    protected $waiting = 0;

    public function __construct(string $token, bool $production, LoopInterface $loop, LoggerInterface $logger, DriverInterface $driver = null)
    {
        $this->token = $token;
        $this->production = $production;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->driver = $driver;
        $this->queue = new SplQueue;
    }

    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    public function get($url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('get', $url, $content, $headers);
    }

    public function post($url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('post', $url, $content, $headers);
    }

    public function put($url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('put', $url, $content, $headers);
    }

    public function patch($url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('patch', $url, $content, $headers);
    }

    public function delete($url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        if (! ($url instanceof Endpoint)) {
            $url = Endpoint::bind($url);
        }

        return $this->queueRequest('delete', $url, $content, $headers);
    }

    public function queueRequest(string $method, Endpoint $url, $content, array $headers = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if (is_null($this->driver)) {
            $deferred->reject(new \Exception('HTTP driver is missing.'));

            return $deferred->promise();
        }

        $headers = array_merge($headers, [
            'User-Agent' => $this->getUserAgent(),
            'access_token' => $this->token,
            'RateLimit-Precision' => 'millisecond',
        ]);

        $baseHeaders = [
            'User-Agent' => $this->getUserAgent(),
            'access_token' => $this->token,
            'RateLimit-Precision' => 'millisecond',
        ];

        if (! is_null($content) && ! isset($headers['Content-Type'])) {
            $content = json_encode($content);

            $baseHeaders['Content-Type'] = 'application/json';
            $baseHeaders['Content-Length'] = strlen($content);
        }

        $headers = array_merge($baseHeaders, $headers);

        $request = new Request($deferred, $method, $this->production, $url, $content ?? '', $headers);
        $this->sortIntoBucket($request);

        return $deferred->promise();
    }

    protected function executeRequest(Request $request, Deferred $deferred = null): ExtendedPromiseInterface
    {
        if ($deferred === null) {
            $deferred = new Deferred();
        }

        $this->driver->runRequest($request)->done(function (ResponseInterface $response) use ($request, $deferred) {
            $data = json_decode((string) $response->getBody());
            $statusCode = $response->getStatusCode();

            if ($statusCode == 429) {

                if (! isset($data->retry_after)) {
                    if ($response->hasHeader('RateLimit-Reset')) {
                        $data->retry_after = $response->getHeader('RateLimit-Reset')[0];
                    } else {

                        $this->logger->error($request. ' does not contain retry after rate-limit value');
                        $rateLimitError = new RuntimeException('No rate limit retry after response', $statusCode);
                        $deferred->reject($rateLimitError);
                        $request->getDeferred()->reject($rateLimitError);
                        return;
                    }
                }

                $rateLimit = new RateLimit($data->global, $data->retry_after);
                $this->logger->warning($request.' hit rate-limit: '.$rateLimit);

                $deferred->reject($rateLimit);
            }

            elseif ($statusCode == 502 || $statusCode == 525) {
                $this->logger->warning($request.' 502/525 - retrying request');

                $this->executeRequest($request, $deferred);
            }

            elseif ($statusCode < 200 || $statusCode >= 300) {
                $error = $this->handleError($response);
                $this->logger->warning($request.' failed: '.$error);

                $deferred->reject($error);
                $request->getDeferred()->reject($error);
            }

            else {
                $this->logger->debug($request.' successful');

                $deferred->resolve($response);
                $request->getDeferred()->resolve($data);
            }
        }, function (Exception $e) use ($request, $deferred) {
            $this->logger->warning($request.' failed: '.$e->getMessage());

            $deferred->reject($e);
            $request->getDeferred()->reject($e);
        });

        return $deferred->promise();
    }

    protected function sortIntoBucket(Request $request): void
    {
        $bucket = $this->getBucket($request->getBucketID());
        $bucket->enqueue($request);
    }

    protected function getBucket(string $key): Bucket
    {
        if (! isset($this->buckets[$key])) {
            $bucket = new Bucket($key, $this->loop, $this->logger, function (Request $request) {
                $deferred = new Deferred();
                $this->queue->enqueue([$request, $deferred]);
                $this->checkQueue();

                return $deferred->promise();
            });

            $this->buckets[$key] = $bucket;
        }

        return $this->buckets[$key];
    }

    protected function checkQueue(): void
    {
        if ($this->waiting >= static::CONCURRENT_REQUESTS || $this->queue->isEmpty()) {
            $this->logger->debug('http not checking', ['waiting' => $this->waiting, 'empty' => $this->queue->isEmpty()]);
            return;
        }

        [$request, $deferred] = $this->queue->dequeue();
        ++$this->waiting;

        $this->executeRequest($request)->then(function ($result) use ($deferred) {
            --$this->waiting;
            $this->checkQueue();
            $deferred->resolve($result);
        }, function ($e) use ($deferred) {
            --$this->waiting;
            $this->checkQueue();
            $deferred->reject($e);
        });
    }

    public function handleError(ResponseInterface $response): Throwable
    {
        $reason = $response->getReasonPhrase().' - ';

        $errorBody = (string) $response->getBody();
        $errorCode = $response->getStatusCode();

        if (($content = json_decode($errorBody)) !== null) {
            if (isset($content->code)) {
                $errorCode = $content->code;
            }
            $reason .= json_encode($content, JSON_PRETTY_PRINT);
        } else {
            $reason .= $errorBody;
        }

        switch ($response->getStatusCode()) {
            case 401:
                return new InvalidTokenException($reason, $errorCode);
            case 403:
                return new NoPermissionsException($reason, $errorCode);
            case 404:
                return new NotFoundException($reason, $errorCode);
            case 500:
                if (strpos(strtolower($errorBody), 'longer than 2000 characters') !== false ||
                    strpos(strtolower($errorBody), 'string value is too long') !== false) {
                    return new ContentTooLongException('Response was more than 2000 characters. Use another method to get this data.', $errorCode);
                }
            default:
                return new RequestFailedException($reason, $errorCode);
        }
    }

    public function getUserAgent(): string
    {
        return 'DiscordBot (https://github.com/gbielbarbosa/asaas-php-http, '.self::VERSION.')';
    }
}