<?php

namespace App\Domain\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;

class RecentDocumentsBuilder
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getList(PageCriteria $criteria)
    {
        $items = $this->executeQuery($criteria);
        $total = $this->getTotalCount($criteria);

        if (count($items)) {
            $boundary_delimiters = [
                'id' => 'getId',
                'updated_at' => 'getUpdatedAt',
            ];

            $col = new PagedCollection($items, $total, $criteria, $boundary_delimiters);

            $offset_criteria = $col->getOffsetCriteria();
            $offset_criteria->setViewer($criteria->getViewer());
            $has_offset = $this->checkForOffset($offset_criteria);

            $col->setHasOffset($has_offset);

            return [
                'items' => $col->getItems(),
                'total' => $col->getTotalCount(),
                'prev_key' => $this->createPageKey($col->getPrevPager()),
                'next_key' => $this->createPageKey($col->getNextPager()),
                'has_prev' => !is_null($col->getPrevPager()),
                'has_next' => !is_null($col->getNextPager()),
            ];
        }

        return [
            'items' => [],
            'total' => 0,
            'prev_key' => '',
            'next_key' => '',
            'has_prev' => false,
            'has_next' => false,
        ];
    }

    public function parsePageKey($key, $per_page): PageCriteria
    {
        $match = preg_match(
            '/^(p|n|N|P)\-([\d]+)\-([\d]{4})([\d]{2})([\d]{2})([\d]{2})([\d]{2})([\d]{2})$/',
            $key,
            $parsed
        );

        if ($match) {
            $direction = 'n' === strtolower($parsed[1]) ? 'NEXT' : 'PREV';
            $bound_id = $parsed[2];
            $bound_date = implode('-', array_slice($parsed, 3, 3)).' '.
                implode(':', array_slice($parsed, 6, 3));
            $criteria = $this->createCriteria($per_page, $direction, $bound_id, $bound_date);
        } else {
            $criteria = $this->createCriteria($per_page);
        }

        return $criteria;
    }

    private function createCriteria($per_page, $direction = null, $boundary_id = null, $boundary_date = null)
    {
        if ($direction && $boundary_id && $boundary_date) {
            $criteria = new PageCriteria($per_page, $direction);
            $criteria->setBoundary('id', $boundary_id);
            $criteria->setBoundary('updated_at', $boundary_date);

            return $criteria;
        } else {
            return new PageCriteria($per_page, 'NEXT');
        }
    }

    private function executeQuery(PageCriteria $criteria)
    {
        list($base_stmt, $base_params) = $this->buildBaseQuery($criteria);
        list($pager_stmt, $pager_params) = $this->buildPagerQuery($criteria);

        $stmt = $base_stmt.$pager_stmt;
        $params = array_merge($base_params, $pager_params);

        $rsm = new Query\ResultSetMappingBuilder($this->entityManager);
        $rsm->addRootEntityFromClassMetadata('App\Entity\Document', 'd');

        $query = $this->entityManager->createNativeQuery($stmt, $rsm);
        $query->setParameters($params);

        $items = $query->getResult();

        if ($criteria->isPrev()) {
            $items = array_reverse($items);
        }

        return $items;
    }

    private function buildBaseQuery(PageCriteria $criteria, $select = 'd.*')
    {
        $docs_table = $this->getTableName('App:Document');
        $docs_tags_table = $this->getAssociationTableName('App:Document', 'tags');
        $read_table = $this->getAssociationTableName('App:Role', 'tags_read');

        $temp_tables = sprintf('WITH visible_docs AS (
            SELECT dt.document_id AS id
            FROM %s dt
            INNER JOIN %s rt ON rt.tag_id = dt.tag_id
            WHERE rt.role_id = :role_id
        ),
        hidden_docs AS (
            SELECT dt.document_id AS id
            FROM %s dt
            WHERE dt.tag_id NOT IN (
                SELECT rt.tag_id FROM %s rt WHERE rt.role_id = :role_id
            )
        ) ', $docs_tags_table, $read_table, $docs_tags_table, $read_table);


        $select_stmt = sprintf('SELECT %s FROM %s d WHERE d.is_removed = :is_removed ', $select, $docs_table);

        $temp_table_where = 'AND EXISTS (SELECT 1 FROM visible_docs vd WHERE d.id = vd.id)
        AND NOT EXISTS (SELECT 1 FROM hidden_docs hd WHERE d.id = hd.id) ';

        $query = $select_stmt;
        $params = [
            'is_removed' => 'false',
        ];

        if ($criteria->getViewer()) {
            $role = $criteria->getViewer();
            if (!$role->getIsSuper()) {
                $query = $temp_tables.$select_stmt.$temp_table_where;
                $params['role_id'] = $role->getId();
            } else {
                $query = $select_stmt;
            }
        }

        return [$query, $params];
    }

    private function buildPagerQuery(PageCriteria $criteria)
    {
        /*
         * Take note of the arrangement of the row value pairs! The last should be an index
         * for deterministic sorting to be followed, so date then id.
         * You match the values with the last item of the previous page that's why it's < for next page.
         * You have to inverse the comparison > and sorting DESC if doing the previous page!
         */
        if ($criteria->hasBoundaries()) {
            if ($criteria->isPrev()) {
                $stmt = 'AND (d.updated_at, d.id) > (:bound_updated_at, :bound_id) '
                    .'ORDER BY d.updated_at ASC, d.id ASC ';
            } else {
                // next
                $stmt = 'AND (d.updated_at, d.id) < (:bound_updated_at, :bound_id) '
                    .'ORDER BY d.updated_at DESC, d.id DESC ';
            }

            $updated_at = $criteria->getBoundary('updated_at');
            if ($updated_at instanceof \DateTimeInterface) {
                $updated_at = $updated_at->format('Y-m-d H:i:s');
            }

            $params = [
                'bound_id' => $criteria->getBoundary('id'),
                'bound_updated_at' => $updated_at,
            ];
        } else {
            $stmt = 'ORDER BY d.updated_at DESC, d.id DESC ';
            $params = [];
        }

        $stmt .= sprintf('LIMIT %d ', $criteria->getMaxResults());

        return [$stmt, $params];
    }

    private function getTotalCount(PageCriteria $criteria)
    {
        list($stmt, $params) = $this->buildBaseQuery($criteria, 'COUNT(d.id) as totalCount');

        $dbal = $this->entityManager->getConnection();
        $query = $dbal->prepare($stmt);
        $query->execute($params);

        return $query->fetchColumn(0);
    }

    private function checkForOffset(PageCriteria $criteria)
    {
        list($base_stmt, $base_params) = $this->buildBaseQuery($criteria, 'd.id');
        list($pager_stmt, $pager_params) = $this->buildPagerQuery($criteria);

        $stmt = $base_stmt.$pager_stmt;
        $params = array_merge($base_params, $pager_params);

        $dbal = $this->entityManager->getConnection();
        $query = $dbal->executeQuery($stmt, $params);

        return false !== $query->fetchColumn(0);
    }

    private function getTableName($doctrine_class)
    {
        return $this->entityManager->getClassMetadata($doctrine_class)->getTableName();
    }

    private function getAssociationTableName($doctrine_class, $association_name)
    {
        $mapping = $this->entityManager->getClassMetadata($doctrine_class)
            ->getAssociationMapping($association_name);

        return $mapping['joinTable']['name'];
    }

    private function createPageKey(?PageCriteria $pager): string
    {
        if (is_null($pager)) {
            return '';
        }

        return sprintf('%s-%s-%s',
            $pager->isPrev() ? 'p' : 'n',
            $pager->getBoundary('id'),
            $pager->getBoundary('updated_at')->format('YmdHis')
        );
    }
}
