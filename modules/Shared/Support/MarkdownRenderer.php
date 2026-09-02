<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Support;

use Illuminate\Support\Str;

final class MarkdownRenderer
{
    public static function toHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public static function toPlainText(string $markdown): string
    {
        $html = self::toHtml($markdown);
        $html = str_replace(['<li>', '</p>', '<br>', '<br />'], ['• ', "</p>\n", "\n", "\n"], $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
