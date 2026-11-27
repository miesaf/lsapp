<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Post bodies are HTML from CKEditor and are shown unescaped. Keep the formatting the editor can
 * produce (headings, bold/italic, links, lists, quotes, tables, media embeds) and drop everything
 * that can run script: <script>, <iframe>, event attributes, style, javascript: links.
 */
final class PostHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowElement('oembed', ['url']) // MediaEmbed output; the browser does nothing with it
                ->allowLinkSchemes(['http', 'https', 'mailto'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeMedias()
                ->withMaxInputLength(-1) // bodies are not truncated
        );

        return self::$sanitizer->sanitize((string) $html);
    }
}
