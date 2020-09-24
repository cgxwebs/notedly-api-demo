<?php

namespace App\Enum;

use Elao\Enum\SimpleChoiceEnum;

final class DocumentFormat extends SimpleChoiceEnum
{
    const Plaintext = 'plaintext';
    const Markdown = 'markdown';
    const Html = 'html';
    const Json = 'json';

    public static function getDescription($value): string
    {
        $desc = [
            self::Plaintext => 'Plaintext',
            self::Markdown => 'Markdown',
            self::Html => 'HTML',
            self::Json => 'JSON',
        ];

        return isset($desc[$value]) ? $desc[$value] : parent::getDescription($value);
    }
}
