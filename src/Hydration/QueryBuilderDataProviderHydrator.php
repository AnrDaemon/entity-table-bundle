<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
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
    ) {
    }

    public function supports(string $className): bool
    {
        return QueryBuilderDataProvider::class === $className;
    }

    // ----
    //  Дегидрация
    // ----

    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => QueryBuilderDataProvider::class];

        /* @var QueryBuilderDataProvider $object */
        $builder = $object->getQueryBuilder();

        $ret['builder']['object'] = iterator_to_array($this->dehydrateObject(
            $builder,
            exlcude: ['dql', 'parameters', 'em']
        ));
        $ret['builder']['params'] = $this->dehydrateQueryBuilderParams($builder);

        return $ret;
    }

    private function dehydrateObject(object $object, array $properties = [], array $exlcude = []): iterable
    {
        if (empty($properties)) {
            $reflection = new \ReflectionObject($object);
            $props = $reflection->getProperties(~\ReflectionProperty::IS_STATIC);
            foreach ($props as $prop) {
                if (!in_array($prop->getName(), $exlcude)) {
                    $properties[] = $prop->getName();
                }
            }
        }

        foreach ($properties as $property) {
            if (in_array($property, $exlcude)) {
                continue;
            }
            try {
                $reflectionProperty = new \ReflectionProperty($object, $property);
                if ($reflectionProperty->isStatic() || !$reflectionProperty->isInitialized($object)) {
                    continue;
                }
                $value = $reflectionProperty->getValue($object);

                if (is_object($value) || is_array($value)) {
                    $value = iterator_to_array($this->dehydrateValue($value));
                }

                yield $property => $value;
            } catch (\ReflectionException) {
                continue;
            }

            yield '@class' => get_class($object);
        }
    }

    public function dehydrateValue(mixed $value): iterable
    {
        if (\is_object($value)) {
            yield from $this->dehydrateObject($value);
        } elseif (\is_array($value)) {
            yield from $this->dehydrateArray($value);
        }
    }

    public function dehydrateArray(array $values): iterable
    {
        foreach ($values as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $value = iterator_to_array($this->dehydrateValue($value));
            }
            yield $key => $value;
        }
    }

    // ----
    //  Гидрация
    // ----

    public function hydrate(array|string $value): QueryBuilderDataProvider
    {
        $qb = new QueryBuilder($this->entityManager);
        $qb = $this->hydrateObject($value['builder']['object'], $qb);

        $qb->setParameters($this->hydrateQueryBuilderParams($value['builder']['params'] ?? []));

        return new QueryBuilderDataProvider($qb);
    }

    private function hydrateObject(array $data, ?object $object = null): object
    {
        if (null === $object) {
            $class = $data['@class'] ?? throw new \InvalidArgumentException('Class for object is not set');
            class_exists($class) or throw new \InvalidArgumentException('Class for object does not exist');

            try {
                $reflection = new \ReflectionClass($class);

                if ($reflection->isEnum()) {
                    return $class::{$data['name']};
                }

                $arguments = [];
                $constructor = $reflection->getConstructor();
                if ($constructor->getNumberOfRequiredParameters() > 0) {
                    $allParameters = $constructor->getParameters();
                    foreach ($allParameters as $constructorParameter) {
                        if ($constructorParameter->isPromoted() && !$constructorParameter->isOptional()) {
                            $name = $constructorParameter->getName();
                            if (isset($data[$name])) {
                                $arguments[$name] = $data[$name];
                            } else {
                                throw new \InvalidArgumentException('Invalid constructor parameter value for: '.$name);
                            }
                        }
                    }
                }

                $object = new $class(...$arguments);
            } catch (\Throwable $exception) {
                throw $exception;
            }

            unset($data['@class']);
        }

        foreach ($data as $property => $value) {
            try {
                if (is_array($value)) {
                    if (!empty($value['@class'])) {
                        $value = $this->hydrateObject($value);
                    } else {
                        $value = $this->hydrateArray($value);
                    }
                }
                $reflectionProperty = new \ReflectionProperty($object, $property);
                $reflectionProperty->setValue($object, $value);
            } catch (\ReflectionException) {
                continue;
            }
        }

        return $object;
    }

    private function hydrateArray(array $values): array
    {
        $ret = [];
        foreach ($values as $k => $value) {
            if (is_array($value)) {
                if (!empty($value['@class'])) {
                    $value = $this->hydrateObject($value);
                } else {
                    $value = $this->hydrateArray($value);
                }
            }
            $ret[$k] = $value;
        }

        return $ret;
    }

    // ----
    //  Параметры
    // ----

    /**
     * Специальный метод для дегидрации параметров.
     * Некоторые из них могут быть сущностями Doctrine, и тогда мы сохраняем только идентификатор такой сущности.
     * Для иных объектов сохраняем класс.
     */
    private function dehydrateQueryBuilderParams(QueryBuilder $builder): array
    {
        $p = [];
        $params = $builder->getParameters()->toArray();

        foreach ($params as $key => $param) {
            $p[$key]['name'] = $param->getName();
            $p[$key]['type'] = $param->getType();
            $value = $param->getValue();

            if (is_object($value)) {
                $p[$key]['@class'] = get_class($value);
                if ($this->doctrineEntityHydration->supports(get_class($value))) {
                    $p[$key]['id'] = $this->doctrineEntityHydration->dehydrate($value);
                } else {
                    foreach ($this->normalizers as $normalizer) {
                        if ($normalizer instanceof NormalizerInterface && $normalizer->supportsNormalization($value)) {
                            $p[$key]['value'] = $normalizer->normalize($value);
                            break;
                        }
                    }
                }
            } else {
                $p[$key]['value'] = $value;
            }
        }

        return $p;
    }

    private function hydrateQueryBuilderParams(array $params): ArrayCollection
    {
        $p = [];

        foreach ($params as $key => $param) {
            if (isset($param['name'], $param['@class'], $param['id']) && $this->doctrineEntityHydration->supports($param['@class'])) {
                $p[] = new Parameter($param['name'], $this->doctrineEntityHydration->hydrate($param['id'], $param['@class']));
            } elseif (isset($param['name'], $param['@class'], $param['value'])) {
                foreach ($this->normalizers as $normalizer) {
                    if ($normalizer instanceof DenormalizerInterface && $normalizer->supportsDenormalization($param['value'], $param['@class'])) {
                        $p[] = new Parameter($param['name'], $normalizer->denormalize($param['value'], $param['@class']));
                        continue 2;
                    }
                }
            } else {
                $p[] = new Parameter($param['name'], $param['value'], $param['type']);
            }
        }

        return new ArrayCollection($p);
    }
}
