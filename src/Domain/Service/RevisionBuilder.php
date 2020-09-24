<?php

namespace App\Domain\Service;

use App\Entity\Document;
use App\Entity\Revision;
use Doctrine\ORM\EntityManagerInterface;
use Webmozart\Assert\Assert;

class RevisionBuilder
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function build(Document $document, string $label = '')
    {
        if (!$this->entityManager->getConnection()->isTransactionActive()) {
            throw new \BadMethodCallException('Transaction is required to be explicitly started.');
        }

        Assert::maxLength($label, 120);

        $data = [
            'title' => $document->getTitle(),
            'format' => $document->getFormat()->getValue(),
            'content' => $document->getContent(),
        ];

        $revision = new Revision();

        $revision->setData($data)
            ->setDocument($document)
            ->setLabel($label);

        return $revision;
    }
}
