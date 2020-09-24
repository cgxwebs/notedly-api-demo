<?php

namespace App\Entity;

use App\Enum\DocumentFormat;

class RevisionData
{
    private string $title;

    private ?DocumentFormat $format;

    private string $content;

    public function __construct($title, DocumentFormat $format, $content)
    {
        $this->title = $title;
        $this->format = $format;
        $this->content = $content;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFormat(): DocumentFormat
    {
        return $this->format;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
