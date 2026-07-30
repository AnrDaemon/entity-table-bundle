<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Attribute;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;

/**
 * Атрибут, управляющий отображением статусов сущностей в таблице.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class TableRowStatuses extends MetadataAttribute
{
    public function __construct(
        public bool $display = true,
        public array $statuses = [],
        public array $exclude = [],
        public readonly array $groups = [MetadataAttribute::DEFAULT_GROUP],
    ) {
    }

    public function getKey(): string
    {
        return 'statuses';
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
