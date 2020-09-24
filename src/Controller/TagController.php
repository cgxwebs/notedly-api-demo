<?php

namespace App\Controller;

use App\Domain\Concerns\ReadWriteSafe;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/tags", name="tags.")
 */
class TagController extends AbstractController
{
    use ApiHelper;
    use ReadWriteSafe;

    /**
     * @var Request
     */
    protected ?Request $request;

    protected ValidatorInterface $validator;

    protected EntityManagerInterface $entityManager;

    public function __construct(
        RequestStack $requestStack,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function list(TagRepository $tagRepository)
    {
        $list = $tagRepository->findBy([], ['name' => 'ASC']);

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
        $this->startTransaction();

        $request = $this->getPostCollection('tag');

        $tag = new Tag();
        $tag->setTitle($request->get('title') ?? '')
            ->setName($request->get('name') ?? '');

        $this->validateOrDie($tag, ['Default', 'NewOrUpdated']);

        $this->commitTransaction([$tag]);

        return $this->json($this->successResponse(
            ['id' => $tag->getId()]
        ));
    }

    /**
     * @Route("/update/{tag<\d+>}", name="update", methods={"PUT"})
     * @IsGranted("ROLE_SUPER")
     */
    public function update(Tag $tag)
    {
        $this->startTransaction();

        $request = $this->getPostCollection('tag');

        $validation_groups = ['Default'];
        if ($request->get('name') !== $tag->getName()) {
            $validation_groups[] = 'NewOrUpdated';
        }

        $tag->setTitle($request->get('title') ?? '')
            ->setName($request->get('name') ?? '');

        $this->validateOrDie($tag, $validation_groups);

        $this->commitTransaction();

        return $this->json($this->successResponse(
            ['id' => $tag->getId()]
        ));
    }

    /**
     * @Route("/remove/{tag<\d+>}", name="remove", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER")
     */
    public function remove(Tag $tag)
    {
        $this->startTransaction();
        $tag_id = $tag->getId();
        $this->entityManager->remove($tag);
        $this->commitTransaction();

        return $this->json($this->successResponse(
            ['id' => $tag_id]
        ));
    }
}
