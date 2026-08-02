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
class PersistentCollectionDataProvider extends AbstractDataProvider implements EntityTableDataProviderInterface
{
    public static function supports($data): bool
    {
        return is_object($data) && $data instanceof PersistentCollection && null !== $data->getOwner();
    }

    public function __construct(
        private PersistentCollection $collection,
        ?Criteria $criteria = null,
    ) {
        if (!isset($criteria)) {
            $this->criteria = Criteria::create();
        } else {
            $this->criteria = $criteria;
        }
    }

    public function getCollection(): Collection&Selectable
    {
        return $this->collection;
    }

    public function getEntityClass(): string
    {
        return $this->collection->getTypeClass()->name;
    }

    public function getTotalCount(): int
    {
        return $this->getMatching()->count();
    }

    public function getDataByPage(int $page = 1): Collection&Selectable
    {
        return new ArrayCollection(
            $this->getMatching()->slice(($page - 1) * $this->pageSize, $this->pageSize)
        );
    }

    private function getMatching(): Collection
    {
        return $this->collection->matching($this->criteria);
    }
}
