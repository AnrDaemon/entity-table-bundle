<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\PersistentCollectionDataProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\LiveComponent\Hydration\HydrationExtensionInterface;

/**
 * Сервис гидрации-дегидрации дата-провайдеров на основе объекта класса PersistentCollection.
 */
class PersistentCollectionDataProviderHydrator implements DataProviderHydratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'ux.live_component.doctrine_entity_hydration_extension')]
        private readonly HydrationExtensionInterface $doctrineEntityHydration,
    ) {
    }

    public function supports(string $className): bool
    {
        return PersistentCollectionDataProvider::class === $className;
    }

    public function hydrate(array|string $value): PersistentCollectionDataProvider
    {
        $ownerClass = $value['owner']['class'] ?? throw new \InvalidArgumentException('Invalid owner class name in dehydrated data');
        $ownerId = $value['owner']['id'] ?? throw new \InvalidArgumentException('Invalid owner id in dehydrated data');

        if (!$this->doctrineEntityHydration->supports($ownerClass) || null === $owner = $this->doctrineEntityHydration->hydrate($ownerId, $ownerClass)) {
            throw new \InvalidArgumentException('Invalid owner in dehydrated data');
        }

        $relationClass = $value['relation']['class'] ?? throw new \InvalidArgumentException('Invalid relation class name in dehydrated data');
        $relationMapping = $value['relation']['mapping'] ?? throw new \InvalidArgumentException('Invalid relation mapping data in dehydrated data');

        $targetClass = $this->entityManager->getClassMetadata($relationMapping['targetEntity']);
        $collection = new PersistentCollection($this->entityManager, $targetClass, new ArrayCollection());
        $collection->setOwner($owner, $relationClass::fromMappingArray($relationMapping));
        $collection->setInitialized(false);

        return new PersistentCollectionDataProvider($collection);
    }

    public function dehydrate(EntityTableDataProviderInterface $object): array
    {
        $ret = ['class' => PersistentCollectionDataProvider::class];

        /* @var PersistentCollectionDataProvider $object */

        /**
         * Мы поддерживаем только коллекции, являющиеся ассоциациями.
         * У них должен быть владелец.
         */
        $owner = $object->getCollection()->getOwner();
        if (null === $owner || !$this->doctrineEntityHydration->supports($owner::class)) {
            throw new \InvalidArgumentException('Invalid owner in hydrated data');
        }

        $ret['owner']['class'] = $owner::class;
        $ret['owner']['id'] = $this->doctrineEntityHydration->dehydrate($owner);

        $ret['relation']['class'] = $object->getCollection()->getMapping()::class;
        $ret['relation']['mapping'] = $object->getCollection()->getMapping()->toArray();

        return $ret;
    }
}
