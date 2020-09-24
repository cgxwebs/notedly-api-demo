<?php

namespace App\Domain\Service\Parser;

interface ContentParserFormat
{
    public function parse($content);
}
