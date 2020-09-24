<?php

namespace App\Controller;

use App\Domain\Service\AuthenticatedRoleFinder;
use App\Domain\Service\RoleManager;
use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/roles", name="roles.")
 */
class RoleController extends AbstractController
{
    use ApiHelper;

    protected EntityManagerInterface $entityManager;

    private RoleRepository $roleRepository;

    protected ?Request $request;

    private UserPasswordEncoderInterface $encoder;

    private RoleManager $roleManager;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $encoder,
        RoleManager $roleManager,
        RoleRepository $roleRepository
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
        $this->roleManager = $roleManager;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @Route("/create-defaults", name="create_defaults")
     */
    public function createDefaults()
    {
        $default_username = 'root';
        $default_password = 'admin123';

        $root_role = new Role();
        $encoded_password = $this->encoder->encodePassword($root_role, $default_password);

        $root_role->setUsername($default_username)
            ->setPassword($encoded_password)
            ->setRoles(['ROLE_SUPER']);

        $this->entityManager->persist($root_role);
        $this->entityManager->flush();

        return $this->json($root_role);
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     * @IsGranted("ROLE_SUPER")
     */
    public function list(RoleRepository $roleRepository)
    {
        $list = $roleRepository->findBy([], ['username' => 'ASC']);

        return $this->json(
            $this->successResponse($list)
        );
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     * @IsGranted("ROLE_SUPER")
     */
    public function create()
    {
        $request = $this->getPostCollection('role');
        $role = $this->roleManager->createRole($request);

        return $this->json($this->successResponse(
            ['id' => $role->getId()]
        ));
    }

    /**
     * @Route("/update/{role<\d+>}", name="update", methods={"PUT"})
     * @IsGranted("ROLE_SUPER")
     */
    public function update(Role $role)
    {
        $request = $this->getPostCollection('role');
        $role = $this->roleManager->updateRole($role, $request);

        return $this->json($this->successResponse(
            ['id' => $role->getId()]
        ));
    }

    /**
     * @Route("/remove/{role<\d+>}", name="remove", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER")
     */
    public function remove(Role $role)
    {
        $role_id = $role->getId();
        $this->roleManager->removeRole($role);

        return $this->json($this->successResponse(
            ['id' => $role_id]
        ));
    }

    /**
     * @Route("/authenticated", name="authenticated", methods={"GET", "POST"})
     */
    public function authenticated(AuthenticatedRoleFinder $authenticatedRoleFinder)
    {
        $role = $authenticatedRoleFinder->getRole();

        return $this->json($this->successResponse(
            ['role' => $role]
        ));
    }
}
