<?php

namespace JarirAhmed\UniversalSpa;

class SpaEngine
{
    /**
     * Start the SPA engine by capturing output buffer.
     * This should be called at the very top of your PHP script or middleware.
     */
    public static function start()
    {
        ob_start(function ($html) {
            return self::processOutput($html);
        });
    }

    /**
     * Process the HTML output and return JSON if it's an SPA request.
     */
    protected static function processOutput($html)
    {
        // Check if this is an SPA request via the custom header
        $isSpaRequest = isset($_SERVER['HTTP_X_FRONTEND_SPA']) && $_SERVER['HTTP_X_FRONTEND_SPA'] === 'true';

        if (!$isSpaRequest) {
            return $html;
        }

        // We are in 'json' mode. Extract title, content, style, script
        $content = '';
        $start = strpos($html, 'data-spa-content');
        if ($start !== false) {
            // Find the opening tag containing data-spa-content
            $openTag = strrpos(substr($html, 0, $start), '<');
            preg_match('/<(\w+)\s[^>]*data-spa-content/', substr($html, $openTag), $tagNameMatch);
            $tagName = $tagNameMatch[1] ?? 'div';
            $innerStart = strpos($html, '>', $start) + 1;
            $innerEnd = strrpos($html, '</' . $tagName . '>');
            if ($innerStart && $innerEnd) {
                $content = trim(substr($html, $innerStart, $innerEnd - $innerStart));
            }
        }

        // Extract styles marked with data-spa-style
        preg_match_all('/<style(?![^>]*data-spa-layout-style)[^>]*>.*?<\/style>/si', $html, $styleMatches);
        $style = implode("\n", $styleMatches[0] ?? []);

        // Extract scripts
        preg_match_all('/<script(?![^>]*data-spa-layout-script)(?![^>]*\bsrc=)[^>]*>.*?<\/script>/si', $html, $scriptMatches);
        $script = implode("\n", $scriptMatches[0] ?? []);

        // Extract title
        preg_match('/<title>(.*?)<\/title>/si', $html, $titleMatch);
        $title = strip_tags($titleMatch[1] ?? '');

        // Output JSON instead of HTML
        header('Content-Type: application/json');
        return json_encode([
            'title'   => $title,
            'style'   => $style,
            'content' => $content,
            'script'  => $script,
        ]);
    }
}
