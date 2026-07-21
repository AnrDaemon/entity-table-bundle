<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/**
 * Провайдер данных на основе объекта класса PersistentCollection.
 * Типовое применение: отображение данных отношений "-ко-многим" сущностей Doctrine.
 */
class PersistentCollectionDataProvider implements EntityTableDataProviderInterface
{
    private int $pageSize = 25;

    public static function supports($data): bool
    {
        return is_object($data) && $data instanceof PersistentCollection && null !== $data->getOwner();
    }

    public function __construct(
        private PersistentCollection $collection,
    ) {
    }

    public function getCollection(): PersistentCollection
    {
        return $this->collection;
    }

    public function getEntityClass(): string
    {
        return $this->collection->getTypeClass()->name;
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        return $this;
    }

    public function withOrder(Criteria $criteria): EntityTableDataProviderInterface
    {
        return $this;
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        $this->pageSize = $size;

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->collection->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        return new ArrayCollection(
            $this->collection->slice(($page - 1) * $this->pageSize, $this->pageSize)
        );
    }
}
