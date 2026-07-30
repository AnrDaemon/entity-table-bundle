<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\QueryDataProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
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
        #[AutowireIterator(tag: 'serializer.normalizer')]
        private readonly iterable $normalizers,
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
            if (isset($param['name'], $param['class'], $param['id']) && $this->doctrineEntityHydration->supports($param['class'])) {
                $params[$param['name']] = $this->doctrineEntityHydration->hydrate($param['id'], $param['class']);
            } elseif (isset($param['name'], $param['class'], $param['value'])) {
                foreach ($this->normalizers as $normalizer) {
                    if ($normalizer instanceof DenormalizerInterface && $normalizer->supportsDenormalization($param['value'], $param['class'])) {
                        $params[$param['name']] = $normalizer->denormalize($param['value'], $param['class']);
                        break 2;
                    }
                }
            }

            $params[$key] = $param;
        }

        $query = new Query($this->entityManager)
            ->setDQL($value['query']['dql'])
            ->setParameters($params);

        return new QueryDataProvider($query);
    }

    /**
     * @param QueryDataProvider $object
     */
    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => QueryDataProvider::class];
        /* @var QueryDataProvider $object */
        $ret['query']['dql'] = $object->getQuery()->getDQL();
        $params = $object->getQuery()->getParameters()->toArray();
        foreach ($params as $key => $param) {
            if (is_object($param->getValue()) && $this->doctrineEntityHydration->supports(get_class($param->getValue()))) {
                $ret['query']['params'][$key]['name'] = $param->getName();
                $ret['query']['params'][$key]['class'] = get_class($param->getValue());
                $ret['query']['params'][$key]['id'] = $this->doctrineEntityHydration->dehydrate($param->getValue());
            } elseif (is_object($param->getValue())) {
                foreach ($this->normalizers as $normalizer) {
                    if ($normalizer instanceof NormalizerInterface && $normalizer->supportsNormalization($param->getValue())) {
                        $ret['query']['params'][$key]['name'] = $param->getName();
                        $ret['query']['params'][$key]['class'] = get_class($param->getValue());
                        $ret['query']['params'][$key]['value'] = $normalizer->normalize($param->getValue());
                        break 2;
                    }
                }

                $ret['query']['params'][$key] = $param;
            }
        }

        return $ret;
    }
}
