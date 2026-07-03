<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class EntityExists extends Constraint
{
    public string $message = 'validate.entity_not_found';
    public string $entityClass;
    public string $field = 'id';

    public function __construct(
        string $entityClass,
        string $field = 'id',
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->entityClass = $entityClass;
        $this->field = $field;
        if ($message !== null) {
            $this->message = $message;
        }
    }
}
