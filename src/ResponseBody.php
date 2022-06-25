<?php

namespace Amp\Http\Client;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\Payload;
use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;

/**
 * This class allows streamed and buffered access to a request body with an API similar to {@see Payload}.
 *
 * The {@see read()} and {@see buffer()} methods can also throw {@see HttpException} in addition to the usual
 * {@see StreamException}, though generally there is no need to catch this exception.
 */
final class ResponseBody implements ReadableStream
{
    use ForbidSerialization;
    use ForbidCloning;

    private readonly Payload $stream;

    public function __construct(ReadableStream|string $stream)
    {
        $this->stream = new Payload($stream);
    }

    /**
     * @throws HttpException
     * @throws StreamException
     */
    public function read(?Cancellation $cancellation = null): ?string
    {
        return $this->stream->read($cancellation);
    }

    /**
     * @throws HttpException
     * @throws BufferException|StreamException
     * @see Payload::buffer()
     */
    public function buffer(?Cancellation $cancellation = null, int $limit = \PHP_INT_MAX): string
    {
        return $this->stream->buffer($cancellation, $limit);
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    /**
     * Indicates the remainder of the request body is no longer needed and will be discarded.
     */
    public function close(): void
    {
        $this->stream->close();
    }

    public function isClosed(): bool
    {
        return $this->stream->isClosed();
    }

    public function onClose(\Closure $onClose): void
    {
        $this->stream->onClose($onClose);
    }
}
