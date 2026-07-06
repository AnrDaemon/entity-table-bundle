<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Mapping;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use SprintF\Metadata\Mapping\ClassMetadata as ClassMetadataAbstract;

class ClassMetadata extends ClassMetadataAbstract
{
    /**
     * @todo: Перенести метод в библиотеку
     */
    private function getDataValue(string $group, string $key)
    {
        return $this->data[$group][$key] ?? $this->data[MetadataAttribute::DEFAULT_GROUP][$key] ?? null;
    }

    public function getLabel(string $group = MetadataAttribute::DEFAULT_GROUP): string
    {
        return $this->getDataValue($group, 'table.label') ?? '';
    }

    public function getInitialOrder(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        return $this->getDataValue($group, 'table.initialOrder') ?? [];
    }

    public function getRoute(string $group = MetadataAttribute::DEFAULT_GROUP): ?string
    {
        return $this->getDataValue($group, 'row.route') ?? null;
    }

    public function getRouteProperties(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        return $this->getDataValue($group, 'row.routeProperties') ?? [];
    }

    public function getPropertiesMetadata(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        $metadata = parent::getPropertiesMetadataByGroups([$group]);
        uasort($metadata, fn (PropertyMetadata $p1, PropertyMetadata $p2) => $p1->getOrder($group) <=> $p2->getOrder($group));

        return $metadata;
    }
}
