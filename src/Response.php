<?php

namespace Amp\Http\Client;

use Amp\ByteStream\ReadableBuffer;
use Amp\ByteStream\ReadableStream;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Future;
use Amp\Http\Message;
use Amp\Http\Status;

/**
 * An HTTP response.
 */
final class Response extends Message
{
    use ForbidSerialization;
    use ForbidCloning;

    private string $protocolVersion;

    private int $status;

    private string $reason;

    private Request $request;

    private ResponseBody $body;

    /** @var Future<Trailers> */
    private Future $trailers;

    private ?Response $previousResponse;

    public function __construct(
        string $protocolVersion,
        int $status,
        ?string $reason,
        array $headers,
        ReadableStream $body,
        Request $request,
        ?Future $trailers = null,
        ?Response $previousResponse = null
    ) {
        $this->setProtocolVersion($protocolVersion);
        $this->setStatus($status, $reason);
        $this->setHeaders($headers);
        $this->setBody($body);
        $this->request = $request;
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->trailers = $trailers ?? Future::complete(new Trailers([]));
        $this->previousResponse = $previousResponse;
    }

    /**
     * Retrieve the HTTP protocol version used for the request.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(string $protocolVersion): void
    {
        if (!\in_array($protocolVersion, ["1.0", "1.1", "2"], true)) {
            throw new \Error(
                "Invalid HTTP protocol version: " . $protocolVersion
            );
        }

        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Retrieve the response's three-digit HTTP status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status, ?string $reason = null): void
    {
        $this->status = $status;
        $this->reason = $reason ?? Status::getReason($status);
    }

    /**
     * Retrieve the response's (possibly empty) reason phrase.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Retrieve the Request instance that resulted in this Response instance.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Retrieve the original Request instance associated with this Response instance.
     *
     * A given Response may be the result of one or more redirects. This method is a shortcut to
     * access information from the original Request that led to this response.
     */
    public function getOriginalRequest(): Request
    {
        if (empty($this->previousResponse)) {
            return $this->request;
        }

        return $this->previousResponse->getOriginalRequest();
    }

    /**
     * Retrieve the original Response instance associated with this Response instance.
     *
     * A given Response may be the result of one or more redirects. This method is a shortcut to
     * access information from the original Response that led to this response.
     */
    public function getOriginalResponse(): Response
    {
        if (empty($this->previousResponse)) {
            return $this;
        }

        return $this->previousResponse->getOriginalResponse();
    }

    /**
     * If this Response is the result of a redirect traverse up the redirect history.
     */
    public function getPreviousResponse(): ?Response
    {
        return $this->previousResponse;
    }

    public function setPreviousResponse(?Response $previousResponse): void
    {
        $this->previousResponse = $previousResponse;
    }

    /**
     * Assign a value for the specified header field by replacing any existing values for that field.
     *
     * @param string $name Header name.
     * @param string|string[] $value Header value.
     */
    public function setHeader(string $name, $value): void
    {
        if (($name[0] ?? ":") === ":") {
            throw new \Error("Header name cannot be empty or start with a colon (:)");
        }

        parent::setHeader($name, $value);
    }

    /**
     * Assign a value for the specified header field by adding an additional header line.
     *
     * @param string $name Header name.
     * @param string|string[] $value Header value.
     */
    public function addHeader(string $name, $value): void
    {
        if (($name[0] ?? ":") === ":") {
            throw new \Error("Header name cannot be empty or start with a colon (:)");
        }

        parent::addHeader($name, $value);
    }

    public function setHeaders(array $headers): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        parent::setHeaders($headers);
    }

    /**
     * Remove the specified header field from the message.
     *
     * @param string $name Header name.
     */
    public function removeHeader(string $name): void
    {
        parent::removeHeader($name);
    }

    /**
     * Retrieve the response body.
     */
    public function getBody(): ResponseBody
    {
        return $this->body;
    }

    public function setBody(ReadableStream|string|int|float|bool|null $body): void
    {
        $this->body = match (true) {
            $body instanceof ResponseBody => $body,
            $body instanceof ReadableStream => new ResponseBody($body),
            \is_string($body), $body === null => new ResponseBody(new ReadableBuffer($body)),
            \is_scalar($body) => new ResponseBody(new ReadableBuffer(\var_export($body, true))),
            default => throw new \TypeError("Invalid body type: " . \get_debug_type($body)),
        };
    }

    /**
     * @return Future<Trailers>
     */
    public function getTrailers(): Future
    {
        return $this->trailers;
    }

    /**
     * @param Future<Trailers> $future
     */
    public function setTrailers(Future $future): void
    {
        $this->trailers = $future;
    }
}
