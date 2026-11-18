<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\Tests\Unit\DTOs;

use Daika7ana\DragonStar\DTOs\Common\Document;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public function testPrefersTheRfc5987Filename(): void
    {
        $document = Document::fromResponse(new Response(200, [
            'Content-Disposition' => "attachment; filename=\"fallback.pdf\"; filename*=UTF-8''etichet%C4%83.pdf",
        ]));

        $this->assertSame('etichetă.pdf', $document->getFilename());
    }

    public function testParsesAnUnquotedFilename(): void
    {
        $document = Document::fromResponse(new Response(200, [
            'Content-Disposition' => 'attachment; filename=awb.pdf',
        ]));

        $this->assertSame('awb.pdf', $document->getFilename());
    }

    public function testFallsBackWhenThereIsNoContentDisposition(): void
    {
        $document = Document::fromResponse(new Response(200));

        $this->assertSame('label.pdf', $document->getFilename('label.pdf'));
    }

    public function testDownloadHeadersFallBackToOctetStreamAndTheDefaultFilename(): void
    {
        $document = Document::fromResponse(new Response(200));

        $this->assertSame([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="label.pdf"',
        ], $document->getDownloadHeaders('label.pdf'));
    }

    public function testContentsCanBeReadRepeatedly(): void
    {
        $document = Document::fromResponse(new Response(200, [], '^XA^XZ'));

        $this->assertSame('^XA^XZ', $document->getContents());
        $this->assertSame('^XA^XZ', $document->getContents());
    }
}
