<?php

namespace App\FormModel;

use App\Entity\Role;
use App\Repository\TagRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RoleFormModel
{
    public const FORM_PROPERTIES = [
        'username' => '', 'password' => '', 'confirm_password' => '', 'change_password' => true,
        'is_super' => false, 'tags_read' => [], 'tags_write' => [],
    ];

    /**
     * @Assert\Regex(App\Entity\Role::NAME_REGEX)
     * @Assert\Length(min=4, max=32)
     */
    private string $username = '';

    /**
     * @Assert\Length(min=6, max=120, groups={"NewOrUpdated"})
     */
    private string $password = '';

    /**
     * @Assert\IdenticalTo(propertyPath="password", message="Passwords do not match.", groups={"NewOrUpdated"})
     */
    private string $confirm_password = '';

    /**
     * @Assert\Type(type="bool")
     */
    private $is_super = false;

    /**
     * @Assert\Type(type="bool")
     */
    private $change_password = false;

    /**
     * @Assert\Type(type="array")
     */
    private $tags_read = [];

    /**
     * @Assert\Type(type="array")
     */
    private $tags_write = [];

    public function __construct(array $input)
    {
        foreach (self::FORM_PROPERTIES as $prop => $default) {
            $this->$prop = $input[$prop] ?? $default;
        }
    }

    public function isChangePassword(): bool
    {
        return $this->change_password;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getConfirmPassword(): string
    {
        return $this->confirm_password;
    }

    public function isIsSuper(): bool
    {
        return $this->is_super;
    }

    public function getTagsRead(): array
    {
        return array_unique($this->tags_read);
    }

    public function getTagsWrite(): array
    {
        return array_unique($this->tags_write);
    }

    public function transformToEntity(
        UserPasswordEncoderInterface $encoder,
        TagRepository $tagRepository
    ) {
        $entity = new Role();

        $entity->setUsername($this->username);

        if ($this->isChangePassword()) {
            $encodedPassword = $encoder->encodePassword($entity, $this->password);
            $entity->setPassword($encodedPassword);
        }

        if ($this->is_super) {
            $entity->setRoles([Role::ROLE_SUPER])
                ->syncTags([], 'tags_read')
                ->syncTags([], 'tags_write');
        } else {
            $tags_read = $tagRepository->findAllById($this->getTagsRead());
            $tags_write = $tagRepository->findAllById($this->getTagsWrite());
            $entity->syncTags($tags_read, 'tags_read')
                ->syncTags($tags_write, 'tags_write');
        }

        return $entity;
    }
}
