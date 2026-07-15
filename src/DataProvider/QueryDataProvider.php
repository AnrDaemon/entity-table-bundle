<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Провайдер данных на основе объекта класса ORM\Query.
 */
class QueryDataProvider implements EntityTableDataProviderInterface
{
    private int $pageSize = 25;

    public static function supports($data): bool
    {
        return is_object($data) && $data instanceof Query;
    }

    public function __construct(
        private readonly Query $query,
    ) {
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getEntityClass(): string
    {
        $ast = $this->query->getAST();
        if ($ast instanceof SelectStatement) {
            $from = $ast->fromClause->identificationVariableDeclarations[0]?->rangeVariableDeclaration->abstractSchemaName;
            if (null !== $from && class_exists($from)) {
                return $from;
            }
        }

        throw new \InvalidArgumentException('Unknow class in select statement');
    }

    /**
     * К сожалению, изменить что-либо в Query не представляется легкой задачей...
     */
    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        return $this;
    }

    /**
     * К сожалению, изменить сортировку в Query не представляется легкой задачей...
     */
    public function withOrder(array|Criteria $order): EntityTableDataProviderInterface
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
        $paginator = new Paginator($this->query);

        return $paginator->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        $paginator = new Paginator($this->query
            ->setFirstResult(($page - 1) * $this->pageSize)
            ->setMaxResults($this->pageSize)
        );

        return new ArrayCollection(
            iterator_to_array($paginator->getIterator())
        );
    }
}
