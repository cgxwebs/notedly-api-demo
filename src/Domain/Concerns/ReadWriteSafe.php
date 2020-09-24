<?php

namespace App\Domain\Concerns;

use Doctrine\ORM\EntityManagerInterface;
use Throwable;

trait ReadWriteSafe
{
    protected EntityManagerInterface $entityManager;

    protected function startTransaction()
    {
        $conn = $this->entityManager->getConnection();

        if (!$conn->isTransactionActive()) {
            $conn->beginTransaction();
        }
    }

    protected function performTransaction(string $method, ...$args)
    {
        $this->startTransaction();
        try {
            $this->$method(...$args);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $t) {
            $this->entityManager->rollback();
            throw $t;
        }
    }

    protected function commitTransaction($cleanEntities = [])
    {
        $this->startTransaction();
        try {
            foreach ($cleanEntities as $entity) {
                $this->entityManager->persist($entity);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $t) {
            $this->entityManager->rollback();
            throw $t;
        }
    }
}
