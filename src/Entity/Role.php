<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=RoleRepository::class)
 * @ORM\Table(name="roles")
 * @UniqueEntity("username")
 * @Serializer\VirtualProperty("tagsReadAsArray", exp="object.getTagsReadAsArray()", options={@Serializer\SerializedName("tagsReadAsArray")})
 * @Serializer\VirtualProperty("tagsWriteAsArray", exp="object.getTagsWriteAsArray()", options={@Serializer\SerializedName("tagsWriteAsArray")})
 */
class Role implements UserInterface
{
    const NAME_REGEX = '/^(([a-z0-9][\_\.]?)*([a-z0-9]))$/';
    const ROLE_SUPER = 'ROLE_SUPER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Ignore()
     * @Serializer\Exclude()
     */
    private $password;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="role_tag_read")
     * @ORM\JoinColumn(nullable=true, unique=true)
     */
    private $tags_read;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     * @ORM\JoinTable(name="role_tag_write")
     * @ORM\JoinColumn(nullable=true, unique=true)
     */
    private $tags_write;

    public function __construct()
    {
        $this->tags_read = new ArrayCollection();
        $this->tags_write = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     * @Ignore()
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     * @Ignore()
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getIsSuper()
    {
        return in_array(self::ROLE_SUPER, $this->getRoles());
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTagsRead(): Collection
    {
        return $this->tags_read;
    }

    public function getTagsReadAsArray(): array
    {
        $pairs = [];
        foreach ($this->getTagsRead() as $tag) {
            /*
             * @var $tag Tag
             */
            $pairs[$tag->getId()] = $tag->getName();
        }

        return $pairs;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTagsWrite(): Collection
    {
        return $this->tags_write;
    }

    public function getTagsWriteAsArray(): array
    {
        $pairs = [];
        foreach ($this->getTagsWrite() as $tag) {
            /*
             * @var $tag Tag
             */
            $pairs[$tag->getId()] = $tag->getName();
        }

        return $pairs;
    }

    public function addTag(Tag $tag, string $tag_container = 'tags_read'): self
    {
        if (!$this->$tag_container->contains($tag)) {
            $this->{$tag_container}[] = $tag;
        }

        return $this;
    }

    public function syncTags($tags, string $tag_container = 'tags_read')
    {
        $this->$tag_container->clear();

        foreach ($tags as $t) {
            $this->addTag($t, $tag_container);
        }

        return $this;
    }

    public function removeTag(Tag $tag, string $tag_container = 'tags_read'): self
    {
        if ($this->$tag_container->contains($tag)) {
            $this->$tag_container->removeElement($tag);
        }

        return $this;
    }
}
