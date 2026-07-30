<?php

namespace SprintF\Bundle\EntityTable\Component;

use Doctrine\Common\Collections\Collection;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\Mapping\ClassMetadata;
use SprintF\Bundle\EntityTable\Mapping\Factory\ClassMetadataFactory;
use SprintF\Bundle\Wolfflow\Entity\EntityInterface;
use SprintF\Bundle\Wolfflow\Status\StatusInterface;
use SprintF\Bundle\Wolfflow\Workflow\WorkflowCollection;
use SprintF\Bundle\Wolfflow\Workflow\WorkflowInterface;
use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use SprintF\ValueObjects\Value\AbstractValue;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Entity:Table')]
class EntityTableComponent
{
    use DefaultActionTrait;

    public function __construct(
        protected readonly ClassMetadataFactory $classMetadataFactory,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly WorkflowCollection $allWorkflow,
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

    /**
     * Данные для отображения таблицы.
     */
    public function getTableData(): Collection
    {
        return $this->data
            ->withOrder($this->getMetadata()->getInitialOrder($this->group))
            ->withPageSize($this->perPage)
            ->getDataByPage($this->page)
        ;
    }

    /**
     * Ссылка на конкретную сущность, для строки таблицы.
     */
    public function getRowUrl($entity): ?string
    {
        if (empty($route = $this->getMetadata()->getRoute($this->group))) {
            return null;
        }

        $params = [];
        foreach ($this->getMetadata()->getRouteProperties($this->group) as $key => $property) {
            if (is_numeric($key)) {
                $params[$property] = $this->propertyAccessor->getValue($entity, $property);
            } else {
                $params[$key] = $this->propertyAccessor->getValue($entity, $property);
            }
        }

        return $this->urlGenerator->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Метод, получающий для заданного свойства в данной сущности его значение, в специальной обертке.
     */
    public function getPropertyValue($entity, string $property): AbstractValue
    {
        $valueClass = $this->getMetadata()->getPropertiesMetadata($this->group)[$property]->getValueClass($this->group);

        return new $valueClass($this->propertyAccessor->getValue($entity, $property));
    }

    /**
     * Следует ли вообще отображать статусы сущностей в таблице?
     */
    public function displayStatuses(): bool
    {
        return $this->getMetadata()->displayStatuses($this->group) && 0 < $this->getStatusesToDisplay();
    }

    /**
     * Объект рабочего процесса сущностей таблицы, если он существует.
     */
    public function getEntityWorkflow(): ?WorkflowInterface
    {
        $class = $this->data->getEntityClass();
        if (!is_a($class, EntityInterface::class, true)) {
            return null;
        }

        $workflowName = $class::getWorkflowName();

        return $this->allWorkflow->findByName($workflowName);
    }

    /**
     * Список статусов, которые требуется отображать для сущностей в таблице.
     *
     * @return StatusInterface[]
     */
    public function getStatusesToDisplay(): array
    {
        $workflow = $this->getEntityWorkflow();
        if (null === $workflow) {
            return [];
        }

        return $workflow->getStatuses();
    }
}
