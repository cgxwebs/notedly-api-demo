<?php

namespace App\Domain\Service;

use App\Entity\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

class PageCriteria
{
    private $limit = 15;
    private ?ArrayCollection $boundaries = null;
    private $direction;
    private ?Role $viewer = null;

    public function __construct(int $limit = 15, string $direction = 'NEXT')
    {
        $this->limit = $limit;
        $this->direction = strtoupper($direction);
        $this->boundaries = new ArrayCollection();

        Assert::inArray($this->direction, ['NEXT', 'PREV']);
    }

    public function getViewer(): ?Role
    {
        return $this->viewer;
    }

    public function setViewer(?Role $viewer): void
    {
        $this->viewer = $viewer;
    }

    public function setBoundary(string $name, $val)
    {
        $this->boundaries->set($name, $val);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getMaxResults(): int
    {
        return $this->limit + 1;
    }

    public function getBoundary(string $name)
    {
        return $this->boundaries->get($name);
    }

    public function getBoundaries(): array
    {
        return $this->boundaries->toArray();
    }

    public function isPrev()
    {
        return 'PREV' === $this->direction;
    }

    public function isNext()
    {
        return 'NEXT' === $this->direction;
    }

    public function hasBoundaries()
    {
        return $this->boundaries->count() > 0;
    }
}
