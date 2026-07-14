<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Hydration;

use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Общий интерфейс для всех сервисов,
 * обеспечивающих гидрацию/дегидрацию дата-провайдеров.
 */
#[AutoconfigureTag('entity_table.data_provider.hydrator')]
interface DataProviderHydratorInterface
{
    /**
     * Метод, проверяющий поддерживает ли данный сервис гидрацию/дегидрацию данного класса дата-провайдеров?
     *
     * @param class-string $className
     */
    public function supports(string $className): bool;

    /**
     * Метод дегидрации: превращает скалярное значение или массив скаляров в объект дата-провайдера.
     */
    public function hydrate(string|array $value): ?EntityTableDataProviderInterface;

    /**
     * Метод гидрации: превращает объект дата-провайдера в скаляр или массив скаляров.
     */
    public function dehydrate(EntityTableDataProviderInterface $object): string|array;
}
