<?php

declare(strict_types=1);

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EntityExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EntityExists) {
            throw new UnexpectedTypeException($constraint, EntityExists::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $repository = $this->entityManager->getRepository($constraint->entityClass);
        $entity = $repository->findOneBy([$constraint->field => $value]);

        if (!$entity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string)$value)
                ->setParameter('{{ field }}', $constraint->field)
                ->addViolation();
        }
    }
}
