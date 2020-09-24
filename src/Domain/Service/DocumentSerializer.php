<?php

namespace App\Domain\Service;

use App\Entity\Document;
use JMS\Serializer\SerializerInterface as JMSSerializer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializer;

/**
 * https://github.com/symfony/symfony/issues/37334
 * There is a known bug here that can't serialize proxy Doctrine objects properly
 * This service uses a different serializer for the UserInterface entities
 * Use until fixed on Symfony 5.2.
 */
class DocumentSerializer
{
    private $symSerializer;

    private $jmsSerializer;

    public function __construct(SymfonySerializer $symSerializer, JMSSerializer $jmsSerializer)
    {
        $this->symSerializer = $symSerializer;

        $this->jmsSerializer = $jmsSerializer;
    }

    public function serializeSkeletal(array $documents, $format = 'array')
    {
        return $this->serialize($documents, true, $format);
    }

    public function serializeFull(array $documents, $format = 'array')
    {
        return $this->serialize($documents, false, $format);
    }

    public function serialize(array $documents, $skeletal = true, $format = 'array')
    {
        $docs_normalized = $this->symSerializer->normalize($documents);

        // Create map of doc ID and author ID pairs
        list($docs_auth_map, $authors) = $this->createDocAndAuthorMap($documents);

        // Serialize each author once
        $serialized_authors = $this->serializeAuthors($authors);

        // Inject to doc
        $docs_normalized = $this->injectToList(
            $docs_normalized,
            $docs_auth_map,
            $serialized_authors,
            $skeletal
        );

        if ('encoded' === $format) {
            return $this->symSerializer->encode($docs_normalized, 'json');
        }

        return $docs_normalized;
    }

    private function injectToList($docs_normalized, $docs_auth_map, $serialized_authors, $skeletal = true)
    {
        foreach ($docs_normalized as $index => $doc) {
            /**
             * @var $doc Document
             */
            $find_auth_id_of_doc = $docs_auth_map[$doc['id']];
            $author = $serialized_authors[$find_auth_id_of_doc];
            $docs_normalized[$index]['author'] = $author;

            if ($skeletal) {
                unset($docs_normalized[$index]['content']);
            }
        }
        return $docs_normalized;
    }

    private function createDocAndAuthorMap(array $docs_normalized)
    {
        /**
         * @var $doc Document
         */
        $docs_auth_map = [];
        $authors = [];

        foreach ($docs_normalized as $doc) {
            $author = $doc->getAuthor();
            $docs_auth_map[$doc->getId()] = $author->getId();
            if (!isset($authors[$author->getId()])) {
                $authors[$author->getId()] = $author;
            }
        }

        return [$docs_auth_map, $authors];
    }

    private function serializeAuthors(array $authors)
    {
        $serialized_authors = [];
        foreach ($authors as $index => $a) {
            $asJson = $this->jmsSerializer->serialize($a, 'json');
            $asArr = json_decode($asJson, true);
            unset($asArr['tagsReadAsArray']);
            unset($asArr['tagsWriteAsArray']);
            unset($asArr['tags_read']);
            unset($asArr['tags_write']);
            unset($asArr['roles']);
            $serialized_authors[$index] = $asArr;
        }

        return $serialized_authors;
    }
}
