<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Common;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A binary response such as a PDF label or a ZPL file.
 */
final readonly class Document
{
    public function __construct(
        public StreamInterface $stream,
        public ?string $contentType = null,
        public ?string $contentDisposition = null,
    ) {}

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            stream: $response->getBody(),
            contentType: $response->getHeaderLine('Content-Type') ?: null,
            contentDisposition: $response->getHeaderLine('Content-Disposition') ?: null,
        );
    }

    public function getFilename(string $default = 'document.pdf'): string
    {
        $contentDisposition = $this->contentDisposition;

        if ($contentDisposition === null || $contentDisposition === '') {
            return $default;
        }

        if (preg_match("/filename\*=UTF-8''([^;]+)/i", $contentDisposition, $matches) === 1) {
            return rawurldecode($matches[1]);
        }

        if (preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $matches) === 1) {
            return $matches[1];
        }

        return $default;
    }

    public function getContents(): string
    {
        if ($this->stream->isSeekable()) {
            $this->stream->rewind();
        }

        return $this->stream->getContents();
    }

    /**
     * @return array{Content-Type: string, Content-Disposition: string}
     */
    public function getDownloadHeaders(string $defaultFilename = 'document.pdf'): array
    {
        return [
            'Content-Type' => $this->contentType ?? 'application/octet-stream',
            'Content-Disposition' => $this->contentDisposition ?? sprintf('attachment; filename="%s"', $this->getFilename($defaultFilename)),
        ];
    }
}
