<?php
declare(strict_types=1);

namespace Advent;

function renderMarkdown(?string $markdown): string
{
    if ($markdown === null || trim($markdown) === '') {
        return '';
    }

    $lines = preg_split('/\R/', str_replace(["\r\n", "\r"], "\n", $markdown)) ?: [];
    $html = '';
    $paragraph = [];
    $list = [];

    $flushParagraph = static function () use (&$html, &$paragraph): void {
        if ($paragraph === []) {
            return;
        }
        $html .= '<p>'.markdownInline(implode(' ', $paragraph)).'</p>';
        $paragraph = [];
    };
    $flushList = static function () use (&$html, &$list): void {
        if ($list === []) {
            return;
        }
        $html .= '<ul>'.implode('', $list).'</ul>';
        $list = [];
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushParagraph();
            $flushList();
            continue;
        }
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            $flushList();
            $level = strlen($match[1]);
            $html .= '<h'.$level.'>'.markdownInline($match[2]).'</h'.$level.'>';
            continue;
        }
        if (preg_match('/^[-*]\s+(.+)$/', $line, $match)) {
            $flushParagraph();
            $list[] = '<li>'.markdownInline($match[1]).'</li>';
            continue;
        }
        if (preg_match('/^---+$/', $line)) {
            $flushParagraph();
            $flushList();
            $html .= '<hr>';
            continue;
        }
        $flushList();
        $paragraph[] = $line;
    }

    $flushParagraph();
    $flushList();
    return $html;
}

function markdownInline(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $text = preg_replace('/&lt;br\s*\/?&gt;/i', '<br>', $text) ?? $text;
    $text = preg_replace_callback(
        '/\[([^\]]+)\]\(((?:https?:\/\/|mailto:|#)[^)]+)\)/',
        static fn(array $match): string => '<a href="'.$match[2].'">'.$match[1].'</a>',
        $text
    ) ?? $text;
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    return preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text) ?? $text;
}
