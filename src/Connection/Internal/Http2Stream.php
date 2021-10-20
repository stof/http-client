<?php

namespace Amp\Http\Client\Connection\Internal;

use Amp\CancellationToken;
use Amp\Deferred;
use Amp\Future;
use Amp\Http\Client\Connection\Stream;
use Amp\Http\Client\Internal\ForbidCloning;
use Amp\Http\Client\Internal\ForbidSerialization;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Pipeline\Subject;
use Revolt\EventLoop;

/**
 * Used in Http2Connection.
 *
 * @internal
 */
final class Http2Stream
{
    use ForbidSerialization;
    use ForbidCloning;

    public int $id;

    public Request $request;

    public ?Response $response = null;

    public ?Deferred $pendingResponse;

    public ?Future $preResponseResolution = null;

    public bool $responsePending = true;

    public ?Subject $body = null;

    public ?Deferred $trailers = null;

    public CancellationToken $originalCancellation;

    public CancellationToken $cancellationToken;

    /** @var int Bytes received on the stream. */
    public int $received = 0;

    public int $serverWindow;

    public int $clientWindow;

    public int $bufferSize;

    public string $requestBodyBuffer = '';

    public Deferred $requestBodyCompletion;

    /** @var int Integer between 1 and 256 */
    public int $weight = 16;

    public int $dependency = 0;

    public ?int $expectedLength = null;

    public Stream $stream;

    public ?Deferred $windowSizeIncrease = null;

    private ?string $watcher;

    public function __construct(
        int $id,
        Request $request,
        Stream $stream,
        CancellationToken $cancellationToken,
        CancellationToken $originalCancellation,
        ?string $watcher,
        int $serverSize,
        int $clientSize
    ) {
        $this->id = $id;
        $this->request = $request;
        $this->stream = $stream;
        $this->cancellationToken = $cancellationToken;
        $this->originalCancellation = $originalCancellation;
        $this->watcher = $watcher;
        $this->serverWindow = $serverSize;
        $this->clientWindow = $clientSize;
        $this->pendingResponse = new Deferred;
        $this->requestBodyCompletion = new Deferred;
        $this->bufferSize = 0;
    }

    public function __destruct()
    {
        if ($this->watcher !== null) {
            EventLoop::cancel($this->watcher);
        }
    }

    public function disableInactivityWatcher(): void
    {
        if ($this->watcher === null) {
            return;
        }

        EventLoop::disable($this->watcher);
    }

    public function enableInactivityWatcher(): void
    {
        if ($this->watcher === null) {
            return;
        }

        EventLoop::disable($this->watcher);
        EventLoop::enable($this->watcher);
    }
}
