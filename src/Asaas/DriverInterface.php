<?php

namespace Asaas\Http;

use Psr\Http\Message\ResponseInterface;
use React\Promise\ExtendedPromiseInterface;

interface DriverInterface
{

    public function runRequest(Request $request): ExtendedPromiseInterface;
}