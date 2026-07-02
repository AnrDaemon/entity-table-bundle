<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Attribute;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use SprintF\ValueObjects\Value\DefaultValue;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TableColumn extends MetadataAttribute
{
    public function __construct(
        public readonly TranslatableInterface $label,
        public readonly int $order = 1,
        public readonly string $valueClass = DefaultValue::class,
        public readonly bool $withUrl = false,

        public readonly array $groups = [MetadataAttribute::DEFAULT_GROUP],
    ) {
    }

    public function getKey(): string
    {
        return 'column';
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
