<?php

namespace App\Controller;

use App\Domain\Service\AuthenticatedRoleFinder;
use App\Domain\Service\DocumentManager;
use App\Domain\Service\DocumentSerializer;
use App\Domain\Service\Parser\ContentParser;
use App\Domain\Service\RecentDocumentsBuilder;
use App\Entity\Document;
use App\Entity\Revision;
use App\Repository\RevisionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/docs", name="docs.")
 */
class DocumentController extends AbstractController
{
    use ApiHelper;

    protected ?Request $request;

    private DocumentManager $documentManager;

    private AuthenticatedRoleFinder $roleFinder;

    private RecentDocumentsBuilder $documentsBuilder;

    private DocumentSerializer $documentSerializer;

    public function __construct(
        RequestStack $requestStack,
        DocumentManager $documentManager,
        AuthenticatedRoleFinder $roleFinder,
        RecentDocumentsBuilder $documentsBuilder,
        DocumentSerializer $documentSerializer
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->documentManager = $documentManager;
        $this->roleFinder = $roleFinder;
        $this->documentsBuilder = $documentsBuilder;
        $this->documentSerializer = $documentSerializer;
    }

    /**
     * @Route("/", name="list", methods={"GET", "POST"})
     */
    public function list()
    {
        $per_page = 10;
        $key_query_name = 'page';

        $pager_key = $this->request->query->get($key_query_name);

        $criteria = $this->documentsBuilder->parsePageKey($pager_key, $per_page);
        $criteria->setViewer($this->roleFinder->getRole());
        $list = $this->documentsBuilder->getList($criteria);

        // Workaround until 5.2 is released
        $docs_list = $this->documentSerializer->serializeSkeletal($list['items']);
        $list['items'] = $docs_list;

        return $this->json(
            $this->successResponse($list)
        );
    }

    /**
     * @Route("/create", name="create", methods={"POST"})
     */
    public function create()
    {
        $documentRequest = $this->getPostCollection('document');
        $tagRequest = $this->getPost('tags', []);
        $author = $this->roleFinder->getRole();
        $document = $this->documentManager->createDocument(
            $documentRequest,
            $tagRequest,
            $author
        );

        return $this->json(
            $this->successResponse([
                'id' => $document->getId(),
            ])
        );
    }

    /**
     * @Route("/update/{document<\d+>}", name="update", methods={"PUT"})
     */
    public function update(Document $document)
    {
        $documentRequest = $this->getPostCollection('document');
        $tagRequest = $this->getPost('tags', []);

        $document = $this->documentManager->updateDocument(
            $document,
            $documentRequest,
            $tagRequest,
        );

        return $this->json(
            $this->successResponse([
                'id' => $document->getId(),
            ])
        );
    }

    /**
     * @Route("/remove/{document<\d+>}", name="remove", methods={"DELETE"})
     */
    public function remove(Document $document)
    {
        $document_id = $document->getId();
        $this->documentManager->removeDocument($document);

        return $this->json(
            $this->successResponse([
                'id' => $document_id,
            ])
        );
    }

    /**
     * @Route("/single/{document<\d+>}", name="single", methods={"GET", "POST"})
     */
    public function single(Document $document, RevisionRepository $revisionRepository)
    {
        // DocumentManager handles permission check
        $parsedContent = $this->documentManager->getDocumentParsedContent($document);
        $revisions = $revisionRepository->findRecentByDocument($document);
        // See DocumentSerializer for reason
        $serialized = $this->documentSerializer->serializeFull([$document]);
        $data = [
            'document' => $serialized[0],
            'parsedContent' => $parsedContent,
            'revisions' => $revisions,
        ];

        return $this->json(
            $this->successResponse($data)
        );
    }

    /**
     * @Route("/revision/{revision<\d+>}", name="revision", methods={"GET", "POST"})
     */
    public function revision(Revision $revision, ContentParser $contentParser)
    {
        $data = $revision->getData();
        $data['createdAt'] = $revision->getCreatedAt();
        $data['label'] = $revision->getLabel();
        $data['documentId'] = $revision->getDocument()->getId();
        $data['parsedContent'] = $contentParser->parse($data['content'], $data['format']);

        return $this->json(
            $this->successResponse($data)
        );
    }

    /**
     * @Route("/restore/{revision<\d+>}", name="restore", methods={"POST"})
     */
    public function restore(Revision $revision)
    {
        $this->documentManager->restoreRevision($revision);

        return $this->json(
            $this->successResponse([
                'id' => $revision->getId(),
                'document_id' => $revision->getDocument()->getId(),
            ])
        );
    }
}
