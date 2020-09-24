<?php

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DocumentAccessVoter extends Voter
{
    const READ = 'READ';

    /**
     * Write is for updating or deleting a document,
     * and using a specific tag!
     */
    const WRITE = 'WRITE';

    const DELETE = 'DELETE';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [
            self::READ,
            self::WRITE,
            self::DELETE,
        ]) && $subject instanceof Document;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /**
         * @var $user    Role
         * @var $subject Document
         */
        if ($user->getIsSuper()) {
            return true;
        }

        $doc_tags = array_keys($subject->getTagsAsArray());
        $write_tags = array_keys($user->getTagsWriteAsArray());
        $read_tags = array_keys($user->getTagsReadAsArray());
        $is_same_author = $subject->getAuthor()->getId() === $user->getId();

        if (self::DELETE === $attribute && $is_same_author) {
            return true;
        }

        if (self::READ === $attribute) {
            $has_read_access = count(array_intersect($doc_tags, $read_tags)) > 0;
            $has_no_unathorized_read = 0 === count(array_diff($doc_tags, $read_tags));

            if ($has_read_access && $has_no_unathorized_read) {
                return true;
            }
        }

        if (self::WRITE === $attribute) {
            $has_write_access = count(array_intersect($doc_tags, $write_tags)) > 0;
            $has_no_unauthorized_write = 0 === count(array_diff($doc_tags, $write_tags));

            if ($is_same_author && $has_write_access && $has_no_unauthorized_write) {
                return true;
            }
        }

        return false;
    }
}
