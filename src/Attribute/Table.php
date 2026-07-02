<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Attribute;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Table extends MetadataAttribute
{
    public function __construct(
        public readonly TranslatableInterface $label,
        public readonly array $initialOrder = [['id', 'ASC']],

        public readonly array $groups = [MetadataAttribute::DEFAULT_GROUP],
    ) {
    }

    public function getKey(): string
    {
        return 'table';
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
