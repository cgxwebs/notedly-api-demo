<?php

namespace App\Entity;

use App\Enum\DocumentFormat;
use App\Repository\DocumentRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=DocumentRepository::class)
 * @ORM\Table(name="documents")
 */
class Document
{
    use Taggable;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="json")
     */
    private $content_meta = [];

    /**
     * @ORM\Column(type="document_format")
     */
    private $format;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update", field={"title", "content"})
     */
    private $updated_at;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinColumn(nullable=true, unique=true)
     */
    protected $tags;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_removed = false;

    /**
     * @ORM\ManyToOne(targetEntity=Role::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Ignore()
     */
    private $author;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->revisions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentMeta(): ?array
    {
        return $this->content_meta;
    }

    public function setContentMeta(array $content_meta): self
    {
        $this->content_meta = $content_meta;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    public function getFormattedDates(): array
    {
        $dt = [];
        foreach (['getCreatedAt' => 'createdAt', 'getUpdatedAt' => 'updatedAt'] as $getter => $attrib) {
            $date = $this->{$getter}();
            $dt[$attrib.'_human'] = $date->longRelativeToNowDiffForHumans();
            $dt[$attrib.'_full'] = $date->toDateTimeString();
            $dt[$attrib.'_pretty'] = $date->toDayDateTimeString();
        }

        return $dt;
    }

    public function setCreatedAt(DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return DocumentFormat
     */
    public function getFormat(): ?DocumentFormat
    {
        return $this->format;
    }

    public function setFormat(DocumentFormat $format)
    {
        $this->format = $format;
        return $this;
    }

    public function getIsRemoved(): ?bool
    {
        return $this->is_removed;
    }

    public function setIsRemoved(bool $is_removed): self
    {
        $this->is_removed = $is_removed;

        return $this;
    }

    public function getTagsAsArray(): array
    {
        $pairs = [];
        foreach ($this->getTags() as $tag) {
            /*
             * @var $tag Tag
             */
            $pairs[$tag->getId()] = $tag->getName();
        }

        return $pairs;
    }

    /**
     * @Ignore()
     */
    public function getAuthor(): ?Role
    {
        return $this->author;
    }

    public function setAuthor(?Role $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @Ignore()
     */
    public function getCacheKey()
    {
        return 'notes.document_'.$this->getId();
    }
}
