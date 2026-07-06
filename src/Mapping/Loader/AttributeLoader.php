<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Mapping\Loader;

use SprintF\Bundle\EntityTable\Attribute\Table;
use SprintF\Bundle\EntityTable\Attribute\TableColumn;
use SprintF\Bundle\EntityTable\Attribute\TableRow;
use SprintF\Bundle\EntityTable\Mapping\ClassMetadata;
use SprintF\Bundle\EntityTable\Mapping\PropertyMetadata;
use SprintF\Metadata\Mapping\Loader\AttributeLoader as AttributeLoaderAbstract;

class AttributeLoader extends AttributeLoaderAbstract
{
    protected static function getKnownAttributes(int $target): array
    {
        return [
            \Attribute::TARGET_CLASS => [Table::class, TableRow::class],
            \Attribute::TARGET_PROPERTY => [TableColumn::class],
            \Attribute::TARGET_METHOD => [TableColumn::class],
        ][$target];
    }

    protected static function getClassMetadataClass(): string
    {
        return ClassMetadata::class;
    }

    protected static function getPropertyMetadataClass(): string
    {
        return PropertyMetadata::class;
    }
}
