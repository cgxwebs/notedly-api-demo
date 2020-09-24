<?php

namespace App\Entity;

use App\Enum\DocumentFormat;
use App\Repository\RevisionRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=RevisionRepository::class)
 * @ORM\Table(name="revisions")
 */
class Revision
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $created_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @ORM\Column(type="json")
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity=Document::class, inversedBy="revisions", cascade={})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $document;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getFormattedDates(): array
    {
        $dt = [];
        foreach (['getCreatedAt' => 'createdAt'] as $getter => $attrib) {
            $date = $this->{$getter}();
            $dt[$attrib.'_human'] = $date->longRelativeToNowDiffForHumans();
            $dt[$attrib.'_full'] = $date->toDateTimeString();
            $dt[$attrib.'_pretty'] = $date->toDayDateTimeString();
        }

        return $dt;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDocumentData(): RevisionData
    {
        return new RevisionData(
            $this->data['title'],
            DocumentFormat::get($this->data['format']),
            $this->data['content'],
        );
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;

        return $this;
    }
}
