<?php

namespace App\Validator;

use App\Repository\TagRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TagNamesAreUniqueValidator extends ConstraintValidator
{
    private TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    public function validate($check, Constraint $constraint)
    {
        if (!is_array($check)) {
            $check = array_map('strtolower', array_map('trim', explode(',', $check)));
        }

        if (empty($check)) {
            return;
        }

        $tag_names = $this->tagRepository->getNames();
        foreach ($check as $name) {
            if (in_array($name, $tag_names)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $name)
                    ->addViolation();
            }
        }
    }
}
