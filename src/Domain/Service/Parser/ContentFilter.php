<?php

namespace App\Domain\Service\Parser;

use App\Domain\StoryContent;
use App\Enums\StoryContentFilter;
use App\Enums\StoryFormat;

final class ContentFilter
{
    public function filter($content, StoryContent $context, array $exceptions = [])
    {
        $methods = get_class_methods($this);
        $filters = StoryContentFilter::values();
        foreach ($methods as $filter) {
            if (in_array($filter, $filters)) {
                if (!in_array($filter, $exceptions)) {
                    $content = $this->$filter($content, $context);
                }
            }
        }

        return $content;
    }

    public function doRelativeUrls($content)
    {
        $pattern = '/(\$url\()([a-zA-Z0-9\-\.\/\?#_&]{1,72})(\)\$)/';
        preg_match_all($pattern, $content, $matches);

        if (is_null($matches)) {
            return $content;
        }

        $count = count($matches[0]);
        for ($i = 0; $i < $count; ++$i) {
            $seek = $matches[0][$i];
            $url = $matches[2][$i];
            $content = str_replace($seek, url($url), $content);
        }

        return $content;
    }

    public function doEscapePlaintext($content, $context)
    {
        if (StoryFormat::Plaintext != $context->getFormat() && StoryFormat::Json != $context->getFormat()) {
            return $content;
        }

        return htmlentities($content, ENT_QUOTES | ENT_HTML401, 'UTF-8', false);
    }

    public function doConvertLinebreaks($content, $context)
    {
        if (StoryFormat::Plaintext != $context->getFormat() && StoryFormat::Json != $context->getFormat()) {
            return $content;
        }

        return nl2br($content);
    }
}
