<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\UX\LiveComponent\Hydration\DoctrineEntityHydrationExtension;

/**
 * Провайдер данных на основе объекта класса PersistentCollection.
 * Типовое применение: отображение данных отношений "-ко-многим" сущностей Doctrine.
 */
class PersistentCollectionDataProvider implements EntityTableDataProviderInterface
{
    public function __construct(
        private PersistentCollection $collection,
    ) {
    }

    private static EntityManagerInterface $entityManager;

    public static function setEntityManager(EntityManagerInterface $entityManager): void
    {
        self::$entityManager = $entityManager;
    }

    private static DoctrineEntityHydrationExtension $doctrineEntityHydration;

    public static function setDoctrineEntityHydration(DoctrineEntityHydrationExtension $doctrineEntityHydration): void
    {
        self::$doctrineEntityHydration = $doctrineEntityHydration;
    }

    public function getEntityClass(): string
    {
        return $this->collection->getTypeClass()->name;
    }

    public function withScope(Criteria $scope): EntityTableDataProviderInterface
    {
        // TODO: Implement withScope() method.
    }

    public function withOrder(array|Criteria $order): EntityTableDataProviderInterface
    {
        // TODO: Implement withOrder() method.
    }

    public function withPageSize(int $size): EntityTableDataProviderInterface
    {
        // TODO: Implement withPageSize() method.
    }

    public function getTotalCount(): int
    {
        return $this->collection->count();
    }

    public function getDataByPage(int $page = 1): Collection
    {
        return $this->collection;
    }

    public static function hydrate(mixed $value): ?static
    {
        $class = $value['class'] ?? throw new \InvalidArgumentException('Invalid data class name in dehydrated data');

        $ownerClass = $value['owner']['class'] ?? throw new \InvalidArgumentException('Invalid owner class name in dehydrated data');
        $ownerId = $value['owner']['id'] ?? throw new \InvalidArgumentException('Invalid owner id in dehydrated data');

        if (!self::$doctrineEntityHydration->supports($ownerClass) || null === $owner = self::$doctrineEntityHydration->hydrate($ownerId, $ownerClass)) {
            return null;
        }

        $relationClass = $value['relation']['class'] ?? throw new \InvalidArgumentException('Invalid relation class name in dehydrated data');
        $relationMapping = $value['relation']['mapping'] ?? throw new \InvalidArgumentException('Invalid relation mapping data in dehydrated data');

        $targetClass = self::$entityManager->getClassMetadata($relationMapping['targetEntity']);
        $collection = new PersistentCollection(self::$entityManager, $targetClass, new ArrayCollection());
        $collection->setOwner($owner, $relationClass::fromMappingArray($relationMapping));
        $collection->setInitialized(false);

        return new $class($collection);
    }

    public function dehydrate(): mixed
    {
        $ret = ['class' => $this::class];

        /**
         * Мы поддерживаем только коллекции, являющиеся ассоциациями.
         * У них должен быть владелец.
         */
        $owner = $this->collection->getOwner();
        if (null === $owner || !self::$doctrineEntityHydration->supports($owner::class)) {
            return null;
        }

        $ret['owner']['class'] = $owner::class;
        $ret['owner']['id'] = self::$doctrineEntityHydration->dehydrate($owner);

        $ret['relation']['class'] = $this->collection->getMapping()::class;
        $ret['relation']['mapping'] = $this->collection->getMapping()->toArray();

        return $ret;
    }
}
