<?php

namespace SprintF\Bundle\EntityTable\Component;

use Doctrine\ORM\QueryBuilder;
use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;

/**
 * @property-read \Symfony\Component\DependencyInjection\ContainerInterface $container
 * @property-read \SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface $data
 * @property-read \Symfony\Component\Form\FormFactoryInterface $formFactory
 * @mixin \SprintF\Bundle\EntityTable\Component\EntityTableComponent
 */
trait EntityTableFiltersTrait
{
    use ComponentWithFormTrait;

    /**
     * Список классов фильтров.
     *
     * @var class-string[] список фильтров
     */
    #[LiveProp(writable: false, fieldName: 'filterClasses')]
    public array $filters = [];

    /**
     * Возвращает массив объектов фильтров.
     *
     * @return \SprintF\Bundle\EntityTable\Filter\EntityTableFilterInterface[]
     */
    protected function getFilters(): array
    {
        $ret = [];
        foreach ($this->filters as $filter) {
            $ret[] = $this->container->get($filter);
        }

        return $ret;
    }

    /**
     * Класс для формы отображения фильтров.
     */
    protected function getFiltersFormTypeClass(): string
    {
        return FormType::class;
    }

    /**
     * Конструктор формы отображения фильтров.
     */
    protected function createFiltersFormBuilder(mixed $data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createNamedBuilder('filters', $this->getFiltersFormTypeClass(), $data, $options);
    }

    /**
     * Создание форм-билдера для формы отображения фильтров.
     */
    protected function getIndexFiltersFormBuilder(): FormBuilderInterface
    {
        $formBuilder = $this->createFiltersFormBuilder();
        $formBuilder->setMethod('GET');

        foreach ($this->getFilters() as $filter) {
            $formBuilder = $filter->modifyFormBuilder($formBuilder, $this->formValues);
        }

        // $formBuilder->add('__clear', SubmitType::class, ['label' => 'Сброс']);

        return $formBuilder;
    }

    /**
     * Применение фильтров к DataProvider-у.
     */
    protected function prepareDataProviderWithFilters(EntityTableDataProviderInterface $data): EntityTableDataProviderInterface
    {
        if (null === $this->getForm()->getData()) {
            $this->getForm()->submit($this->extractFormValues($this->getForm()->createView()));
        }

        foreach ($this->getFilters() as $filter) {
            $data = $data->withScope($filter->getScope());
        }

        return $data;
    }
}
