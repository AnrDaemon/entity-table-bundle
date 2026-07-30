<?php

namespace SprintF\Bundle\EntityTable\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\FormBuilder;

/**
 * Фильтр для таблицы с данными.
 */
class AbstractFilter implements EntityTableFilterInterface
{
    public function modifyFormBuilder(FormBuilder $formBuilder, array $formValues = []): FormBuilder
    {
        return $formBuilder;
    }

    #[\Override]
    public function getScope(): Criteria
    {
        return Criteria::create();
    }

    public function filterCollection(Collection $collection, $data): Collection
    {
        return $collection;
    }
}
