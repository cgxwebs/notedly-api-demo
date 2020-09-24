<?php

namespace App\FormModel;

use App\Entity\Document;
use App\Enum\DocumentFormat;
use Elao\Enum\Bridge\Symfony\Validator\Constraint\Enum;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentFormModel
{
    /**
     * @Assert\Length(max="120")
     * @Assert\NotBlank()
     */
    private string $title = '';

    private string $content = '';

    /**
     * @Enum(class="App\Enum\DocumentFormat", asValue=true)
     */
    private string $format = '';

    public function __construct(array $input)
    {
        $this->title = $input['title'];
        $this->content = $input['content'];
        $this->format = $input['format'];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFormatAsEnum()
    {
        return DocumentFormat::get($this->getFormat());
    }

    public function transformToEntity()
    {
        $entity = new Document();

        $entity->setTitle($this->getTitle())
            ->setContent($this->getContent())
            ->setFormat($this->getFormatAsEnum());

        return $entity;
    }
}
