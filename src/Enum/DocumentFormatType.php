<?php

namespace App\Enum;

use Elao\Enum\Bridge\Doctrine\DBAL\Types\AbstractEnumType;

final class DocumentFormatType extends AbstractEnumType
{
    protected function getEnumClass(): string
    {
        return DocumentFormat::class;
    }

    public function getName()
    {
        return 'document_format';
    }
}
