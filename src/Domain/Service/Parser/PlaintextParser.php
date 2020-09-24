<?php

namespace App\Domain\Service\Parser;

final class PlaintextParser implements ContentParserFormat
{
    public function parse($content)
    {
        return nl2br(htmlentities($content, ENT_QUOTES | ENT_HTML401 | ENT_SUBSTITUTE, "UTF-8"));
    }
}
