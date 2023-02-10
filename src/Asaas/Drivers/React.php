<?php

namespace Asaas\Http\Drivers;

use Asaas\Http\DriverInterface;
use Asaas\Http\Request;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\ExtendedPromiseInterface;
use React\Socket\Connector;

class React implements DriverInterface
{

    protected $loop;

    protected $browser;

    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        $browser = new Browser($loop, new Connector($loop, $options));
        $this->browser = $browser->withRejectErrorResponse(false);
    }

    public function runRequest(Request $request): ExtendedPromiseInterface
    {
        return $this->browser->{$request->getMethod()}(
            $request->getUrl(),
            $request->getHeaders(),
            $request->getContent()
        );
    }
}