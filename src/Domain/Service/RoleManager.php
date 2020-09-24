<?php

namespace App\Domain\Service;

use App\Controller\ApiHelper;
use App\Domain\Concerns\ReadWriteSafe;
use App\Entity\Role;
use App\Exception\ApiException;
use App\FormModel\RoleFormModel;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoleManager
{
    use ApiHelper;
    use ReadWriteSafe;

    protected EntityManagerInterface $entityManager;

    private TagRepository $tagRepository;

    protected ValidatorInterface $validator;

    private UserPasswordEncoderInterface $encoder;

    public function __construct(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        UserPasswordEncoderInterface $encoder
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->tagRepository = $tagRepository;
        $this->encoder = $encoder;
    }

    public function createRole(
        ArrayCollection $roleRequest
    ) {
        $this->startTransaction();
        $form = $this->createModelAndValidate($roleRequest, true);

        $role = $this->transformFormToEntity($form);
        $this->validateOrDie($role);

        $this->commitTransaction([
            $role,
        ]);

        return $role;
    }

    public function updateRole(
        Role $role,
        ArrayCollection $roleRequest
    ) {
        $this->startTransaction();
        $form = $this->createModelAndValidate($roleRequest, false);

        $forUpdate = $this->transformFormToEntity($form);

        if ('root' === strtolower($role->getUsername())) {
            throw new ApiException(ApiException::ROOT_CHANGE_ERROR);
        }

        if ($role->getUsername() !== $forUpdate->getUsername()) {
            $this->validateOrDie($forUpdate);
            $role->setUsername($forUpdate->getUsername());
        }

        if ($form->isChangePassword()) {
            $role->setPassword($forUpdate->getPassword());
        }

        $role->setRoles($forUpdate->getRoles())
            ->syncTags($forUpdate->getTagsRead(), 'tags_read')
            ->syncTags($forUpdate->getTagsWrite(), 'tags_write');

        $this->commitTransaction();

        return $role;
    }

    public function removeRole(
        Role $role
    ) {
        $this->startTransaction();

        if ('root' === strtolower($role->getUsername())) {
            throw new ApiException(ApiException::ROOT_CHANGE_ERROR);
        }

        $this->entityManager->remove($role);
        $this->commitTransaction();
    }

    private function createModelAndValidate(
        $roleRequest,
        $forcePasswordChange = false): RoleFormModel
    {
        $form = new RoleFormModel([
            'username' => $roleRequest->get('username') ?? '',
            'password' => $roleRequest->get('password') ?? '',
            'confirm_password' => $roleRequest->get('confirmPassword') ?? '',
            'change_password' => $forcePasswordChange ? true : ($roleRequest->get('changePassword') ?? true),
            'is_super' => $roleRequest->get('isSuper') ?? false,
            'tags_read' => $roleRequest->get('tagsRead') ?? [],
            'tags_write' => $roleRequest->get('tagsWrite') ?? [],
        ]);

        $validationGroups = ['Default'];
        if ($form->isChangePassword()) {
            $validationGroups[] = 'NewOrUpdated';
        }

        $this->validateOrDie($form, $validationGroups);

        return $form;
    }

    private function transformFormToEntity(RoleFormModel $form)
    {
        return $form->transformToEntity(
            $this->encoder,
            $this->tagRepository
        );
    }
}
