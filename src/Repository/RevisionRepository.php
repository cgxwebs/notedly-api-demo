<?php

namespace App\Repository;

use App\Entity\Revision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Revision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Revision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Revision[]    findAll()
 * @method Revision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    public function findRecentByDocument($document)
    {
        $res = $this->createQueryBuilder('r')
            ->andWhere('r.document = :document_id')
            ->orderBy('r.created_at', 'DESC')
            ->setMaxResults(15)
            ->setParameter('document_id', $document->getId())
            ->getQuery()
            ->getResult()
            ;

        $items = [];

        foreach ($res as $rev) {
            /*
             * @var $rev Revision
             */
            $items[] = array_merge([
                'id' => $rev->getId(),
                'label' => $rev->getLabel(),
            ], $rev->getFormattedDates());
        }

        return $items;
    }
}
