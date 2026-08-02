<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
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
        private ?Criteria $criteria = null,
    ) {
        if (!isset($criteria)) {
            $this->criteria = Criteria::create();
        }
    }

    public function getCollection(): Collection&Selectable
    {
        return $this->collection->matching($this->criteria);
    }

    public function getEntityClass(): string
    {
        return $this->collection->getTypeClass()->name;
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        $newConstraints = $scope->getWhereExpression();

        if (null === $newConstraints) {
            return $this;
        }

        $oldConstraints = $this->criteria->getWhereExpression();
        if (null === $oldConstraints) {
            $this->criteria->where($newConstraints);
        } else {
            $this->criteria->where(
                Criteria::expr()->andX($oldConstraints, $newConstraints)
            );
        }

        return $this;
    }

    public function withOrder(Criteria $criteria): EntityTableDataProviderInterface
    {
        $newOrdering = $criteria->orderings();
        if (empty($newOrdering)) {
            return $this;
        }

        $oldOrdering = $this->criteria->orderings();
        if (empty($oldOrdering)) {
            $this->criteria->orderBy($newOrdering);
        } else {
            $this->criteria->orderBy(\array_diff_key($oldOrdering, $newOrdering) + $newOrdering);
        }

        return $this;
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        $this->pageSize = $size;

        return $this;
    }

    public function getTotalCount(): int
    {
        return $this->getCollection()->count();
    }

    public function getDataByPage(int $page = 1): Collection&Selectable
    {
        return new ArrayCollection(
            $this->getCollection()->slice(($page - 1) * $this->pageSize, $this->pageSize)
        );
    }
}
