<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\QueryDataProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * Сервис гидрации-дегидрации дата-провайдеров на основе объекта класса ORM\Query.
 */
class QueryDataProviderHydrator implements DataProviderHydratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'ux.live_component.doctrine_entity_hydration_extension')]
        private readonly HydrationExtensionInterface $doctrineEntityHydration,
    ) {
    }

    public function supports(string $className): bool
    {
        return QueryDataProvider::class === $className;
    }

    public function hydrate(array|string $value): QueryDataProvider
    {
        $p = $value['query']['params'] ?? [];
        $params = [];
        foreach ($p as $key => $param) {
            if (isset($param['name'], $param['class']) && $this->doctrineEntityHydration->supports($param['class'])) {
                $params[$param['name']] = $this->doctrineEntityHydration->hydrate($param['id'], $param['class']);
            } else {
                $params[$key] = $param;
            }
        }
        
        $query = new Query($this->entityManager)
            ->setDQL($value['query']['dql'])
            ->setParameters($params);
        ;

        return new QueryDataProvider($query);
    }

    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => QueryDataProvider::class];
        /* @var QueryDataProvider $object */
        $ret['query']['dql'] = $object->getQuery()->getDQL();
        $params = $object->getQuery()->getParameters()->toArray();
        foreach ($params as $key => $param) {
            if ($this->doctrineEntityHydration->supports(get_class($param->getValue()))) {
                $ret['query']['params'][$key]['name'] = $param->getName();
                $ret['query']['params'][$key]['class'] = get_class($param->getValue());
                $ret['query']['params'][$key]['id'] = $this->doctrineEntityHydration->dehydrate($param->getValue());
            } else {
                $ret['query']['params'][$key] = $param;
            }
        }

        return $ret;
    }
}
