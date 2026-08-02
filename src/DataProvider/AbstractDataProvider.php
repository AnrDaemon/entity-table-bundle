<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Абстрактный класс провайдера данных.
 *
 * Содержит базовые реализации основных методов.
 */
abstract class AbstractDataProvider implements EntityTableDataProviderInterface
{
    protected Criteria $criteria;

    protected int $pageSize = 25;

    abstract public static function supports($data): bool;

    abstract public function getEntityClass(): string;

    /**
     * FIXME: Фильтрация по `date > :date` и `date < :date` в двух разных вызовах вернёт только последнее условие.
     */
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
            $this->criteria->orderBy(\array_merge($newOrdering, $newOrdering));
        }

        return $this;
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        $this->pageSize = $size;

        return $this;
    }

    abstract public function getTotalCount(): int;

    abstract public function getDataByPage(int $page = 1): Collection;
}
