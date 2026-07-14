<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\QueryDataProvider;

/**
 * Сервис гидрации-дегидрации дата-провайдеров на основе объекта класса ORM\Query.
 */
class QueryDataProviderHydrator implements DataProviderHydratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $className): bool
    {
        return QueryDataProvider::class === $className;
    }

    public function hydrate(array|string $value): QueryDataProvider
    {
        $query = new Query($this->entityManager)
            ->setDQL($value['query']['dql'])
            ->setParameters($value['query']['params'])
        ;

        return new QueryDataProvider($query);
    }

    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => QueryDataProvider::class];
        /* @var QueryDataProvider $object */
        $ret['query']['dql'] = $object->getQuery()->getDQL();
        $ret['query']['params'] = $object->getQuery()->getParameters()->toArray();

        return $ret;
    }
}
