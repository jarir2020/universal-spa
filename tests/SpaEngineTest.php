<?php

namespace JarirAhmed\UniversalSpa\Tests;

use JarirAhmed\UniversalSpa\SpaEngine;
use PHPUnit\Framework\TestCase;

class SpaEngineTest extends TestCase
{
    private function doc(string $body): string
    {
        return '<!DOCTYPE html><html><head><title>My Page</title>'
            . '<style data-spa-layout-style>.layout{}</style>'
            . '<style>.page{color:red}</style>'
            . '<script data-spa-layout-script>layout()</script>'
            . '<script src="/app.js"></script>'
            . '<script>page()</script>'
            . '</head><body>' . $body . '</body></html>';
    }

    public function testTitleExtracted()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main data-spa-content>Hi</main>'));
        $this->assertSame('My Page', $p['title']);
    }

    public function testContentIsInnerHtmlOfMarkedElement()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main data-spa-content><p>Hello</p></main>'));
        $this->assertStringContainsString('<p>Hello</p>', $p['content']);
    }

    /** The regression: nested same-name tags + later closing tags must not be slurped in. */
    public function testNestedTagsDoNotOverrunContent()
    {
        $body = '<div data-spa-content>'
              . '<div class="card"><div class="inner">Deep</div></div>'
              . '</div>'
              . '<footer><div>FOOTER MARKER</div></footer>';
        $p = SpaEngine::toSpaPayload($this->doc($body));

        $this->assertStringContainsString('Deep', $p['content']);
        $this->assertStringContainsString('class="card"', $p['content']);
        $this->assertStringNotContainsString('FOOTER MARKER', $p['content']); // old code grabbed this
    }

    public function testStyleExcludesLayoutStyle()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main data-spa-content>x</main>'));
        $this->assertStringContainsString('.page{color:red}', $p['style']);
        $this->assertStringNotContainsString('.layout{}', $p['style']);
    }

    public function testScriptExcludesLayoutAndSrcScripts()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main data-spa-content>x</main>'));
        $this->assertStringContainsString('page()', $p['script']);
        $this->assertStringNotContainsString('layout()', $p['script']);
        $this->assertStringNotContainsString('app.js', $p['script']);
    }

    public function testEmptyHtmlGivesEmptyPayload()
    {
        $this->assertSame(
            ['title' => '', 'style' => '', 'content' => '', 'script' => ''],
            SpaEngine::toSpaPayload('')
        );
    }

    public function testMissingContentMarkerYieldsEmptyContent()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main>no marker</main>'));
        $this->assertSame('', $p['content']);
    }

    public function testPayloadIsJsonEncodable()
    {
        $p = SpaEngine::toSpaPayload($this->doc('<main data-spa-content><p>Caf&eacute;</p></main>'));
        $this->assertIsString(json_encode($p));
        $this->assertArrayHasKey('content', $p);
    }
}
