<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Провайдер данных на основе объекта класса ORM\QueryBuilder.
 */
class QueryBuilderDataProvider extends AbstractDataProvider implements EntityTableDataProviderInterface
{
    public static function supports($data): bool
    {
        return is_object($data) && $data instanceof QueryBuilder;
    }

    public function __construct(
        private readonly QueryBuilder $builder,
        ?Criteria $criteria = null,
    ) {
        if (!isset($criteria)) {
            $this->criteria = Criteria::create();
        } else {
            $this->criteria = $criteria;
        }
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function getEntityClass(): string
    {
        return $this->builder->getRootEntities()[0] ?? throw new \InvalidArgumentException('Unknow root entity');
    }

    public function getTotalCount(): int
    {
        return new Paginator($this->getMatching())->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        $paginator = new Paginator(
            $this->getMatching()
                ->setFirstResult(($page - 1) * $this->pageSize)
                ->setMaxResults($this->pageSize)
        );

        return new ArrayCollection(
            \iterator_to_array($paginator->getIterator())
        );
    }

    private function getMatching(): QueryBuilder
    {
        return $this->builder->addCriteria($this->criteria);
    }
}
