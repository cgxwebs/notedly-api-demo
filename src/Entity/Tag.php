<?php

namespace App\Entity;

use App\Repository\TagRepository;
use App\Validator\TagNamesAreUnique;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="tags")
 * @ORM\Entity(repositoryClass=TagRepository::class)
 */
class Tag
{
    public const NAME_REGEX = '/^(([a-z0-9][\_\.]?)*([a-z0-9]))$/';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @TagNamesAreUnique(groups={"NewOrUpdated"})
     * @Assert\Regex(App\Entity\Tag::NAME_REGEX)
     * @Assert\Length(min=1, max=72)
     *
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=200)
     */
    private $title;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
