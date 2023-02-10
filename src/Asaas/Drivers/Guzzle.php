<?php


namespace Asaas\Http\Drivers;

use Asaas\Http\DriverInterface;
use Asaas\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

class Guzzle implements DriverInterface
{

    protected $loop;

    protected $client;


    public function __construct(?LoopInterface $loop = null, array $options = [])
    {
        $this->loop = $loop;

        $options['http_errors'] = false;
        $this->client = new Client($options);
    }

    public function runRequest(Request $request): ExtendedPromiseInterface
    {
        
        $deferred = new Deferred();
        $reactPromise = $deferred->promise();

        $promise = $this->client->requestAsync($request->getMethod(), $request->getUrl(), [
                RequestOptions::HEADERS => $request->getHeaders(),
                RequestOptions::BODY => $request->getContent(),
            ])->then([$deferred, 'resolve'], [$deferred, 'reject']);

        if ($this->loop) {
            $this->loop->futureTick([$promise, 'wait']);
        } else {
            $promise->wait();
        }

        return $reactPromise;
    }
}