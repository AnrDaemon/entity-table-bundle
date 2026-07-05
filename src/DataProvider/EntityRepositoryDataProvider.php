<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

class EntityRepositoryDataProvider implements EntityTableDataProviderInterface
{
    protected QueryBuilder $queryBuilder;

    public function __construct(
        protected ServiceEntityRepository $repository,
    ) {
        $this->queryBuilder = $this->repository->createQueryBuilder($this->getRootAlias());
    }

    public function getEntityClass(): string
    {
        return $this->repository->getClassName();
    }

    /**
     * Алиас для основной сущности, по которой строится таблица.
     */
    protected function getRootAlias(): string
    {
        return 'entity';
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        $scope = clone $scope;
        $scope->orderBy([]);
        $this->queryBuilder->addCriteria($scope);

        return $this;
    }

    public function withOrder(array|Criteria $order): EntityTableDataProviderInterface
    {
        $this->queryBuilder->orderBy(is_array($order) ? $order : $order->getOrderings());
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        // TODO: Implement withPageSize() method.
    }

    public function getTotalCount(): int
    {
        // TODO: Implement getTotalDataCount() method.
    }

    public function getDataByPage(int $page = 1): Collection
    {
        // TODO: Implement getDataByPage() method.
    }

    public static function hydrate(mixed $value): ?static
    {
        return null;
    }

    public function dehydrate(): mixed
    {
        return [
        ];
    }
}
