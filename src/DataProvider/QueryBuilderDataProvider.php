<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\QueryExpressionVisitor;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Провайдер данных на основе объекта класса ORM\QueryBuilder.
 */
class QueryBuilderDataProvider implements EntityTableDataProviderInterface
{
    private int $pageSize = 25;

    public static function supports($data): bool
    {
        return is_object($data) && $data instanceof QueryBuilder;
    }

    public function __construct(
        private readonly QueryBuilder $builder,
    ) {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function getEntityClass(): string
    {
        return $this->builder->getRootEntities()[0] ?? throw new \InvalidArgumentException('Unknow root entity');
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        $expression = $scope->getWhereExpression();

        if (null === $expression) {
            return $this;
        }

        $visitor = new QueryExpressionVisitor([]);
        $queryExpression = $visitor->dispatch($expression);
        $this->builder->andWhere($queryExpression);

        foreach ($visitor->getParameters() as $parameter) {
            $this->builder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType(),
            );
        }

        return $this;
    }

    public function withOrder(Criteria $criteria): EntityTableDataProviderInterface
    {
        $i = 1;
        foreach ($criteria->orderings() as $field => $ordering) {
            if (1 === $i) {
                $this->builder->orderBy($field, $ordering->value);
            } else {
                $this->builder->addOrderBy($field, $ordering->value);
            }

            ++$i;
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
        $paginator = new Paginator($this->builder);

        return $paginator->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        $paginator = new Paginator(
            $this->builder
                ->setFirstResult(($page - 1) * $this->pageSize)
                ->setMaxResults($this->pageSize)
        );

        return new ArrayCollection(
            iterator_to_array($paginator->getIterator())
        );
    }
}
