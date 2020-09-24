<?php

namespace App\Repository;

use App\Domain\Service\RecentDocumentsBuilder;
use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{

    /**
     * @var RecentDocumentsBuilder
     */
    private RecentDocumentsBuilder $documentsBuilder;

    public function __construct(ManagerRegistry $registry, RecentDocumentsBuilder $documentsBuilder)
    {
        parent::__construct($registry, Document::class);
        $this->documentsBuilder = $documentsBuilder;
    }

}
