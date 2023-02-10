<?php

namespace Asaas\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use SplQueue;

class Bucket
{

    protected $queue;

    protected $name;

    protected $loop;

    protected $logger;

    protected $runRequest;

    protected $checkerRunning = false;

    protected $requestLimit;

    protected $requestRemaining;

    protected $resetTimer;

    public function __construct(string $name, LoopInterface $loop, LoggerInterface $logger, callable $runRequest)
    {
        $this->queue = new SplQueue;
        $this->name = $name;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->runRequest = $runRequest;
    }

    public function enqueue(Request $request)
    {
        $this->queue->enqueue($request);
        $this->logger->debug($this.' queued '.$request);
        $this->checkQueue();
    }

    public function checkQueue()
    {
        if ($this->checkerRunning) {
            return;
        }

        $checkQueue = function () use (&$checkQueue) {
            if ($this->requestRemaining < 1 && ! is_null($this->requestRemaining)) {
                $interval = 0;
                if ($this->resetTimer) {
                    $interval = $this->resetTimer->getInterval() ?? 0;
                }
                $this->logger->info($this.' expecting rate limit, timer interval '.($interval * 1000).' ms');
                $this->checkerRunning = false;

                return;
            }

            if ($this->queue->isEmpty()) {
                $this->checkerRunning = false;

                return;
            }

            $request = $this->queue->dequeue();

            ($this->runRequest)($request)->done(function (ResponseInterface $response) use (&$checkQueue) {
                $resetAfter = (float) $response->getHeaderLine('RateLimit-Reset');
                $limit = $response->getHeaderLine('RateLimit-Limit');
                $remaining = $response->getHeaderLine('RateLimit-Remaining');

                if ($resetAfter) {
                    $resetAfter = (float) $resetAfter;

                    if ($this->resetTimer) {
                        $this->loop->cancelTimer($this->resetTimer);
                    }

                    $this->resetTimer = $this->loop->addTimer($resetAfter, function () {
                        $this->requestRemaining = $this->requestLimit;
                        $this->resetTimer = null;
                        $this->checkQueue();
                    });
                }

                if (is_numeric($limit)) {
                    $this->requestLimit = (int) $limit;
                }

                if (is_numeric($remaining)) {
                    $this->requestRemaining = (int) $remaining;
                }

                $checkQueue();
            }, function ($rateLimit) use (&$checkQueue, $request) {
                if ($rateLimit instanceof RateLimit) {
                    
                    $this->queue->enqueue($request);
                    $this->checkerRunning = false;
                    $this->logger->debug($this.' stopping queue checker');
                    
                } else {
                    $checkQueue();
                }
            });
        };

        $this->checkerRunning = true;
        $checkQueue();
    }

    public function __toString()
    {
        return 'BUCKET '.$this->name;
    }
}