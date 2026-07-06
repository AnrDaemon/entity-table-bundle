<?php

namespace SprintF\Bundle\EntityTable\Component;

use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\Mapping\ClassMetadata;
use SprintF\Bundle\EntityTable\Mapping\Factory\ClassMetadataFactory;
use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Entity:Table')]
class EntityTableComponent
{
    use DefaultActionTrait;

    public function __construct(
        protected readonly ClassMetadataFactory $classMetadataFactory,
    ) {
    }

    /**
     * Текущая страница постраничного отображения.
     */
    #[LiveProp(writable: true)]
    public int $page = 1;

    public function resetPage(): void
    {
        $this->page = 1;
    }

    /**
     * Количество элементов на страницу для постраничного отображения.
     */
    #[LiveProp(writable: true, onUpdated: 'onPerPageUpdated')]
    public int $perPage = 25;

    public function onPerPageUpdated($previousValue): void
    {
        if ($previousValue !== $this->perPage) {
            $this->resetPage();
        }
    }

    /**
     * Количество страниц в таблице.
     */
    public function getPagesCount(): int
    {
        return ceil($this->data->getTotalCount() / $this->perPage);
    }

    /**
     * Группа метаданных, по которым будет строиться таблица.
     */
    #[LiveProp(writable: false)]
    public string $group = MetadataAttribute::DEFAULT_GROUP;

    /**
     * Данные для построения таблицы, в специальной "обёртке".
     */
    #[LiveProp(writable: false)]
    public EntityTableDataProviderInterface $data;

    /**
     * Метаданные для класса данных, полученные от атрибутов этого класса и его свойств.
     */
    public function getMetadata(): ClassMetadata
    {
        return $this->classMetadataFactory->getMetadataFor($this->data->getEntityClass());
    }
}
