<?php

namespace App\Domain\Service\Parser;

use League\CommonMark\GithubFlavoredMarkdownConverter;

final class MarkdownParser extends HtmlParser implements ContentParserFormat
{
    public function parse($content)
    {
        $markdownConverter = new GithubFlavoredMarkdownConverter([
            'renderer' => [
                'block_separator' => '',
                'inner_separator' => '',
                'soft_break' => '',
            ],
            'enable_em' => true,
            'enable_strong' => true,
            'use_asterisk' => true,
            'use_underscore' => true,
            'unordered_list_markers' => ['-', '*', '+'],
            'html_input' => 'allow', // Allow Purifier to handle this
            'allow_unsafe_links' => true,
            'max_nesting_level' => 10,
        ]);
        $markdown = $markdownConverter->convertToHtml($content);

        return parent::parse($markdown);
    }
}
