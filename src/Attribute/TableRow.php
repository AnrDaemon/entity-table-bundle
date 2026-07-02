<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Attribute;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class TableRow extends MetadataAttribute
{
    public function __construct(
        public readonly string $route,
        public readonly array $routeProperties = [],

        public readonly array $groups = [MetadataAttribute::DEFAULT_GROUP],
    ) {
        if (empty($route)) {
            throw new \InvalidArgumentException('Маршрут до страницы отдельной сущности не может быть пустой строкой');
        }
    }

    public function getKey(): string
    {
        return 'row';
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
