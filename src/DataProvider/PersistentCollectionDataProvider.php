<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/**
 * Провайдер данных на основе объекта класса PersistentCollection.
 * Типовое применение: отображение данных отношений "-ко-многим" сущностей Doctrine.
 */
class PersistentCollectionDataProvider implements EntityTableDataProviderInterface
{
    public function __construct(
        private PersistentCollection $collection,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->collection->getTypeClass()->name;
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        // TODO: Implement withScope() method.
    }

    public function withOrder(array|Criteria $order): EntityTableDataProviderInterface
    {
        // TODO: Implement withOrder() method.
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        // TODO: Implement withPageSize() method.
    }

    public function getTotalDataCount(): int
    {
        return $this->collection->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        // TODO: Implement getDataByPage() method.
    }
}