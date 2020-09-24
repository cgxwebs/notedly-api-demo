<?php

namespace App\Domain\Service\Parser;

class HtmlParser implements ContentParserFormat
{
    private $purifier;

    public function __construct(\HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    public function parse($content)
    {
        return $this->purifier->purify($content);
    }
}
