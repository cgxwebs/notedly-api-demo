<?php

namespace App\Domain\Service;

use Doctrine\Common\Collections\ArrayCollection;

class PagedCollection
{

    private ?ArrayCollection $items;
    private ?PageCriteria $criteria;

    /**
     * @var array
     *            A pair of name (key) and entity getters used to create boundaries
     *            for accessing next and previous pages
     */
    private array $boundary_delimiters;

    private $total_count;

    private $has_offset = false;

    public function __construct(
        array $items,
        int $total_count,
        PageCriteria $criteria,
        array $boundary_delimiters = []
    ) {
        $this->items = new ArrayCollection($items);
        $this->criteria = $criteria;
        $this->total_count = $total_count;
        $this->boundary_delimiters = $boundary_delimiters;
    }

    public function getItems()
    {
        /**
         * Paged Collection logic makes use of the optimal "row value" retrieval approach, no page numbers
         * It retrieves N+1 items to check the availability of next page
         * and N-1 to check for the prev page existence (both relative to criteria's direction).
         * For queries, use < for upper bound (first item) delimiters to get items of previous page,
         * and > lower bound (last item or Nth item) for next page.
         */

        $items = clone $this->items;

        if (!$this->isLastPage()) {
            if ($this->criteria->isNext()) {
                $items->remove(count($items) - 1);
            } else {
                $items->remove(0);
            }
        }

        // Resets 0-indexing
        return array_values($items->toArray());
    }

    public function getTotalCount(): int
    {
        return $this->total_count;
    }

    public function getPrevPager(): ?PageCriteria
    {
        if ($this->criteria->isNext() && false === $this->has_offset) {
            return null;
        }

        if ($this->criteria->isPrev() && $this->isLastPage()) {
            return null;
        }

        $bound = $this->getFirst();

        return $this->buildSimpleCriteria($bound, 'PREV');
    }

    public function getNextPager()
    {
        if ($this->criteria->isPrev() && false === $this->has_offset) {
            return null;
        }

        if ($this->criteria->isNext() && $this->isLastPage()) {
            return null;
        }

        $bound = $this->getLast();

        return $this->buildSimpleCriteria($bound, 'NEXT');
    }

    public function getFirst()
    {
        if ($this->criteria->isPrev()) {
            return $this->items->get(1);
        } else {
            return $this->items->first();
        }
    }

    public function getLast()
    {
        if ($this->criteria->isPrev()) {
            return $this->items->last();
        } else {
            return $this->items->get($this->criteria->getLimit() - 1);
        }
    }

    /**
     * Offset is relative to direction, used to check for availability of next/prev pages.
     * If direction is next, it would be used as a boundary to check for a prev page.
     * We use offset because we get N+1 to the direction of the paging, but not its opposite side.
     */
    public function getOffsetCriteria()
    {
        if ($this->criteria->isNext()) {
            $bound = $this->items->first();
            $direction = 'PREV';
        } else {
            $bound = $this->items->last();
            $direction = 'NEXT';
        }

        return $this->buildSimpleCriteria($bound, $direction, 1);
    }

    public function setHasOffset(bool $val)
    {
        $this->has_offset = $val;
    }

    private function buildSimpleCriteria($bound, $direction, $limit = null)
    {
        $criteria = new PageCriteria(
            !is_null($limit) ? $limit : $this->criteria->getLimit(),
            $direction
        );

        foreach ($this->boundary_delimiters as $name => $getter) {
            $criteria->setBoundary($name, $bound->$getter());
        }

        return $criteria;
    }

    /**
     * @return bool
     *              Last page is relative to direction, if next then there is still a next page, and vice versa
     */
    private function isLastPage()
    {
        return $this->items->count() !== $this->criteria->getMaxResults();
    }
}
