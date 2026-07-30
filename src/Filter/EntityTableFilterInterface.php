<?php

namespace SprintF\Bundle\EntityTable\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\FormBuilder;

/**
 * Фильтр для таблицы с данными.
 */
interface EntityTableFilterInterface
{
    public function modifyFormBuilder(FormBuilder $formBuilder, array $formValues = []): FormBuilder;

    public function getScope(): Criteria;

    public function filterCollection(Collection $collection, $data): Collection;
}
