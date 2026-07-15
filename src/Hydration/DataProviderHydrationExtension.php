<?php

namespace SprintF\Bundle\EntityTable\Hydration;

use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * Сервис, который обеспечивает гидрацию/дегидрацию всех дата-провайдеров, выбирая подходящий гидратор/дегидратор.
 */
class DataProviderHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        #[AutowireIterator('entity_table.data_provider.hydrator')]
        private readonly iterable $hydrators,
    ) {
    }

    public function supports(string $className): bool
    {
        return EntityTableDataProviderInterface::class === $className;
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        $class = $value['class'] ?? throw new \InvalidArgumentException('Invalid class name in dehydrated data');

        foreach ($this->hydrators as $hydrator) {
            /** @var DataProviderHydratorInterface $hydrator */
            if ($hydrator->supports($class)) {
                return $hydrator->hydrate($value);
            }
        }

        throw new \InvalidArgumentException('Class can not be hydrated: '.$class);
    }

    public function dehydrate(object $object): mixed
    {
        foreach ($this->hydrators as $hydrator) {
            /** @var DataProviderHydratorInterface $hydrator */
            if ($hydrator->supports($object::class)) {
                return $hydrator->dehydrate($object);
            }
        }

        throw new \InvalidArgumentException('Object can not be dehydrated: '.get_class($object));
    }
}
