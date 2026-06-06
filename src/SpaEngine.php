<?php

namespace JarirAhmed\UniversalSpa;

class SpaEngine
{
    /**
     * Start the SPA engine by capturing the output buffer.
     * Call this at the very top of your PHP script or middleware.
     */
    public static function start(): void
    {
        ob_start(static function ($html) {
            return self::processOutput($html);
        });
    }

    /**
     * Output-buffer callback: when the request is an SPA request, replace the HTML
     * body with the JSON payload; otherwise pass the HTML through unchanged.
     */
    protected static function processOutput($html)
    {
        $isSpaRequest = isset($_SERVER['HTTP_X_FRONTEND_SPA'])
            && $_SERVER['HTTP_X_FRONTEND_SPA'] === 'true';

        if (!$isSpaRequest) {
            return $html;
        }

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        return json_encode(self::toSpaPayload($html));
    }

    /**
     * Parse a full HTML document into the SPA payload using a real DOM parser
     * (robust against nested tags, unlike string/regex scanning).
     *
     *  - content: inner HTML of the first element carrying [data-spa-content]
     *  - style:   inline <style> blocks not marked data-spa-layout-style
     *  - script:  inline <script> blocks (no src) not marked data-spa-layout-script
     *  - title:   text of <title>
     *
     * @return array{title:string,style:string,content:string,script:string}
     */
    public static function toSpaPayload(string $html): array
    {
        $payload = ['title' => '', 'style' => '', 'content' => '', 'script' => ''];

        if (trim($html) === '') {
            return $payload;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        // The XML encoding hint makes DOMDocument treat the input as UTF-8.
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($doc);

        // content — inner HTML of the [data-spa-content] element
        $contentNode = $xpath->query('//*[@data-spa-content]')->item(0);
        if ($contentNode !== null) {
            $inner = '';
            foreach ($contentNode->childNodes as $child) {
                $inner .= $doc->saveHTML($child);
            }
            $payload['content'] = trim($inner);
        }

        // styles (excluding the layout style)
        $styles = [];
        foreach ($xpath->query('//style[not(@data-spa-layout-style)]') as $style) {
            $styles[] = $doc->saveHTML($style);
        }
        $payload['style'] = implode("\n", $styles);

        // scripts (inline only, excluding the layout script)
        $scripts = [];
        foreach ($xpath->query('//script[not(@data-spa-layout-script) and not(@src)]') as $script) {
            $scripts[] = $doc->saveHTML($script);
        }
        $payload['script'] = implode("\n", $scripts);

        // title
        $titleNode = $xpath->query('//title')->item(0);
        if ($titleNode !== null) {
            $payload['title'] = trim($titleNode->textContent);
        }

        return $payload;
    }
}
