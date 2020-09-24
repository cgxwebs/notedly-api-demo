<?php

namespace App\Domain\Service;

use App\Controller\ApiHelper;
use App\Domain\Concerns\ReadWriteSafe;
use App\Domain\Service\Parser\ContentParser;
use App\Entity\Document;
use App\Entity\Revision;
use App\Entity\Role;
use App\FormModel\DocumentFormModel;
use App\Repository\TagRepository;
use App\Security\Voter\DocumentAccessVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DocumentManager
{
    use ApiHelper, ReadWriteSafe;

    private ContentParser $contentParser;

    private AuthenticatedRoleFinder $roleFinder;

    private AuthorizationCheckerInterface $authorizationChecker;

    private TagRepository $tagRepository;

    private CacheInterface $cache;

    protected ValidatorInterface $validator;

    private RevisionBuilder $revisionBuilder;

    protected EntityManagerInterface $entityManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        RevisionBuilder $revisionBuilder,
        TagRepository $tagRepository,
        CacheInterface $cache,
        ContentParser $contentParser,
        AuthenticatedRoleFinder $roleFinder
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->revisionBuilder = $revisionBuilder;
        $this->tagRepository = $tagRepository;
        $this->cache = $cache;
        $this->contentParser = $contentParser;
        $this->roleFinder = $roleFinder;
    }

    public function getDocumentParsedContent(Document $document)
    {
        $this->canRead($document);

        $contentParser = $this->contentParser;

        return $this->cache->get($document->getCacheKey(), function () use ($contentParser, $document) {
            return $contentParser->parse($document->getContent(), $document->getFormat());
        });
    }

    public function createDocument(
        ArrayCollection $documentRequest,
        array $tagRequest,
        Role $author
    ) {
        $this->startTransaction();
        $form = $this->createModelAndValidate($documentRequest);
        $document = $form->transformToEntity();
        $document->setAuthor($author);

        if (count($tagRequest) > 0) {
            $document->syncTags(
                $this->tagRepository->findAllById($tagRequest)
            );
        }

        $this->canWrite($document);
        $this->commitTransaction([
            $document,
        ]);
        $this->cache->delete($document->getCacheKey());

        return $document;
    }

    public function updateDocument(
        Document $document,
        ArrayCollection $documentRequest,
        array $tagRequest
    ) {
        $this->startTransaction();
        $form = $this->createModelAndValidate($documentRequest);
        $revision = $this->revisionBuilder->build($document);

        $document->syncTags(
            $this->tagRepository->findAllById($tagRequest)
        );

        $document->setTitle($form->getTitle())
            ->setContent($form->getContent())
            ->setFormat($form->getFormatAsEnum());

        $this->canWrite($document);
        $this->commitTransaction([
            $revision,
        ]);
        $this->cache->delete($document->getCacheKey());

        return $document;
    }

    public function removeDocument(
        Document $document
    ) {
        $this->canDelete($document);
        $this->startTransaction();
        $this->entityManager->remove($document);
        $this->commitTransaction();
        $this->cache->delete($document->getCacheKey());
    }

    public function restoreRevision(Revision $revision)
    {
        $document = $revision->getDocument();
        $revisionData = $revision->getDocumentData();

        $this->canRead($document);
        $this->canWrite($document);
        $this->startTransaction();

        $preRestoreRevision = $this->revisionBuilder->build($document, 'Pre-restore');

        $document->setTitle($revisionData->getTitle())
            ->setContent($revisionData->getContent())
            ->setFormat($revisionData->getFormat());

        $this->entityManager->remove($revision);
        $this->commitTransaction([
            $preRestoreRevision,
        ]);

        $this->cache->delete($document->getCacheKey());
    }

    private function createModelAndValidate($documentRequest): DocumentFormModel
    {
        $form = new DocumentFormModel([
            'title' => $documentRequest->get('title') ?? '',
            'content' => $documentRequest->get('content') ?? '',
            'format' => $documentRequest->get('format') ?? '',
        ]);

        $this->validateOrDie($form);

        return $form;
    }

    private function canRead($subject)
    {
        $this->authorizeOrDie(DocumentAccessVoter::READ, $subject);
    }

    private function canWrite($subject)
    {
        $this->authorizeOrDie(DocumentAccessVoter::WRITE, $subject);
    }

    private function canDelete($subject)
    {
        $this->authorizeOrDie(DocumentAccessVoter::DELETE, $subject);
    }

    private function authorizeOrDie($attribute, $subject)
    {
        if (!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException('Access Denied.');
        }
    }
}
