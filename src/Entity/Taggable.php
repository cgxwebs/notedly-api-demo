<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait Taggable
{
    protected $tags;

    /**
     * @return Collection|Tag[]
     */
    public function getTags(string $tag_container = 'tags'): Collection
    {
        return $this->$tag_container ?? new ArrayCollection();
    }

    public function addTag(Tag $tag, string $tag_container = 'tags'): self
    {
        if (!$this->$tag_container->contains($tag)) {
            $this->{$tag_container}[] = $tag;
        }

        return $this;
    }

    public function syncTags($tags, string $tag_container = 'tags')
    {
        $this->$tag_container->clear();

        foreach ($tags as $t) {
            $this->addTag($t, $tag_container);
        }

        return $this;
    }

    public function removeTag(Tag $tag, string $tag_container = 'tags'): self
    {
        if ($this->$tag_container->contains($tag)) {
            $this->$tag_container->removeElement($tag);
        }

        return $this;
    }
}
