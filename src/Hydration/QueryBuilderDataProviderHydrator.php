<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\QueryBuilderDataProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * Сервис гидрации-дегидрации дата-провайдеров на основе объекта класса ORM\QueryBuilder.
 */
class QueryBuilderDataProviderHydrator implements DataProviderHydratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'ux.live_component.doctrine_entity_hydration_extension')]
        private readonly HydrationExtensionInterface $doctrineEntityHydration,
        #[AutowireIterator(tag: 'serializer.normalizer')]
        private readonly iterable $normalizers,
        private readonly DenormalizerInterface&NormalizerInterface $serializer,
    ) {
    }

    public function supports(string $className): bool
    {
        return QueryBuilderDataProvider::class === $className;
    }

    public function hydrate(array|string $value): QueryBuilderDataProvider
    {
        $qb = new \Doctrine\ORM\QueryBuilder($this->entityManager);

        foreach ($value['builder']['parts'] as $name => $part) {
            if (null === $part) {
                continue;
            }
            switch ($name) {
                case 'distinct':
                    $qb->distinct($part);
                    break;
                case 'select':
                    foreach ($part as $p) {
                        $qb->addSelect($p['parts']);
                    }
                    break;
                case 'from':
                    foreach ($part as $p) {
                        $qb->from($p['from'], $p['alias'], $p['indexBy']);
                    }
                    break;
                case 'join':
                    foreach ($part as $root => $joins) {
                        foreach ($joins as $join) {
                            switch ($join['joinType']) {
                                case 'INNER':
                                    $qb->innerJoin($join['join'], $join['alias'], $join['conditionType'], $join['condition'], $join['indexBy']);
                                    break;
                                case 'LEFT':
                                    $qb->leftJoin($join['join'], $join['alias'], $join['conditionType'], $join['condition'], $join['indexBy']);
                                    break;
                            }
                        }
                    }
                    break;
                case 'orderBy':
                    foreach ($part as $p) {
                        foreach ($p['parts'] as $order) {
                            $qb->addOrderBy(...explode(' ', $order));
                        }
                    }
                    break;
            }
        }

        $p = $value['builder']['params'] ?? [];
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

        $qb->setParameters(new ArrayCollection($params));

        return new QueryBuilderDataProvider($qb);
    }

    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => QueryBuilderDataProvider::class];

        /* @var QueryBuilderDataProvider $object */
        $builder = $object->getQueryBuilder();

        $parts = $builder->getDQLParts();
        $ret['builder']['parts'] = $this->serializer->normalize($parts);

        $params = $builder->getParameters()->toArray();
        foreach ($params as $key => $param) {
            if (is_object($param->getValue()) && $this->doctrineEntityHydration->supports(get_class($param->getValue()))) {
                $ret['builder']['params'][$key]['name'] = $param->getName();
                $ret['builder']['params'][$key]['class'] = get_class($param->getValue());
                $ret['builder']['params'][$key]['id'] = $this->doctrineEntityHydration->dehydrate($param->getValue());
            } elseif (is_object($param->getValue())) {
                foreach ($this->normalizers as $normalizer) {
                    if ($normalizer instanceof NormalizerInterface && $normalizer->supportsNormalization($param->getValue())) {
                        $ret['builder']['params'][$key]['name'] = $param->getName();
                        $ret['builder']['params'][$key]['class'] = get_class($param->getValue());
                        $ret['builder']['params'][$key]['value'] = $normalizer->normalize($param->getValue());
                        break 2;
                    }
                }

                $ret['builder']['params'][$key] = $param;
            }
        }

        return $ret;
    }
}
