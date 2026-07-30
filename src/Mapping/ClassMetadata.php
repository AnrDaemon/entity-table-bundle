<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Mapping;

use Doctrine\Common\Collections\Criteria;
use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use SprintF\Metadata\Mapping\ClassMetadata as ClassMetadataAbstract;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class ClassMetadata extends ClassMetadataAbstract
{
    /**
     * @todo: Перенести метод в библиотеку
     */
    private function getDataValue(string $group, string $key)
    {
        return $this->data[$group][$key] ?? $this->data[MetadataAttribute::DEFAULT_GROUP][$key] ?? null;
    }

    public function getLabel(string $group = MetadataAttribute::DEFAULT_GROUP): TranslatableInterface
    {
        return $this->getDataValue($group, 'table.label') ?? new TranslatableMessage('');
    }

    public function getInitialOrder(string $group = MetadataAttribute::DEFAULT_GROUP): Criteria
    {
        return $this->getDataValue($group, 'table.initialOrder') ?? new Criteria();
    }

    public function getRoute(string $group = MetadataAttribute::DEFAULT_GROUP): ?string
    {
        return $this->getDataValue($group, 'row.route') ?? null;
    }

    public function getRouteProperties(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        return $this->getDataValue($group, 'row.routeProperties') ?? [];
    }

    public function displayStatuses(string $group = MetadataAttribute::DEFAULT_GROUP): bool
    {
        return $this->getDataValue($group, 'statuses.display') ?? false;
    }

    public function getStatusesIncluded(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        return $this->getDataValue($group, 'statuses.included') ?? [];
    }

    public function getStatusesExcluded(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        return $this->getDataValue($group, 'statuses.excluded') ?? [];
    }

    public function getPropertiesMetadata(string $group = MetadataAttribute::DEFAULT_GROUP): array
    {
        $metadata = parent::getPropertiesMetadataByGroups([$group]);
        uasort($metadata, fn (PropertyMetadata $p1, PropertyMetadata $p2) => $p1->getOrder($group) <=> $p2->getOrder($group));

        return $metadata;
    }
}
