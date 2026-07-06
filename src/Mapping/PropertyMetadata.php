<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Mapping;

use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use SprintF\Metadata\Mapping\PropertyMetadata as PropertyMetadataAbstract;
use SprintF\ValueObjects\Value\DefaultValue;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class PropertyMetadata extends PropertyMetadataAbstract
{
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @todo: Перенести метод в библиотеку
     */
    private function getDataValue(string $group, string $key)
    {
        return $this->data[$group][$key] ?? $this->data[MetadataAttribute::DEFAULT_GROUP][$key] ?? null;
    }

    public function getLabel(string $group = MetadataAttribute::DEFAULT_GROUP): TranslatableInterface
    {
        return $this->getDataValue($group, 'column.label') ?? new TranslatableMessage('');
    }

    public function getOrder(string $group = MetadataAttribute::DEFAULT_GROUP): int
    {
        return $this->getDataValue($group, 'column.order') ?? 0;
    }

    public function getValueClass(string $group = MetadataAttribute::DEFAULT_GROUP): string
    {
        return $this->getDataValue($group, 'column.valueClass') ?? DefaultValue::class;
    }

    public function withUrl(string $group = MetadataAttribute::DEFAULT_GROUP): bool
    {
        return $this->getDataValue($group, 'column.withUrl') ?? false;
    }
}
