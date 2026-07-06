<?php

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * Handles hydration of Doctrine persistent collections.
 */
class DataProviderHydrationExtension implements HydrationExtensionInterface
{
    public function __construct(
        /** Этот сервис нам нужен для повторого использования уже сделанной корректной гидрации сущностей Doctrine */
        #[Autowire(service: 'ux.live_component.doctrine_entity_hydration_extension')]
        private readonly HydrationExtensionInterface $doctrineEntityHydration,

        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $className): bool
    {
        return EntityTableDataProviderInterface::class === $className;
    }

    public function hydrate(mixed $value, string $className): ?object
    {
        $class = $value['class'] ?? throw new \InvalidArgumentException('Invalid class name in dehydrated data');

        if (method_exists($class, 'setEntityManager')) {
            $class::setEntityManager($this->entityManager);
        }
        if (method_exists($class, 'setDoctrineEntityHydration')) {
            $class::setDoctrineEntityHydration($this->doctrineEntityHydration);
        }

        return $class::hydrate($value);
    }

    public function dehydrate(object $object): mixed
    {
        if (method_exists($object, 'setEntityManager')) {
            $object->setEntityManager($this->entityManager);
        }
        if (method_exists($object, 'setDoctrineEntityHydration')) {
            $object->setDoctrineEntityHydration($this->doctrineEntityHydration);
        }

        return $object->dehydrate();
    }
}
