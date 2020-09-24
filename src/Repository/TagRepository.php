<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function getIDs()
    {
        $res = $this->createQueryBuilder('t')
            ->select('t.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($res, 'id');
    }

    public function getNames()
    {
        $res = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getArrayResult();

        return array_column($res, 'name');
    }

    public function findAllById(array $list)
    {
        return $this->createQueryBuilder('t')
            ->where('t.id IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->getResult();
    }
}
