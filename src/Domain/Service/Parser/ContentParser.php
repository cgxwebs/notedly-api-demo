<?php

namespace App\Domain\Service\Parser;

class ContentParser
{
    private $htmlParser;
    private $plaintextParser;
    private MarkdownParser $markdownParser;

    public function __construct(
        HtmlParser $htmlParser,
        MarkdownParser $markdownParser,
        PlaintextParser $plaintextParser
    ) {
        $this->htmlParser = $htmlParser;
        $this->plaintextParser = $plaintextParser;
        $this->markdownParser = $markdownParser;
    }

    public function parse(string $content, string $format)
    {
        $format = strtolower(trim($format)).'Parser';

        if (isset($this->$format)) {
            return $this->$format->parse($content);
        }

        throw new \InvalidArgumentException('Parser format does not exists, provided: '.$format);
    }
}
