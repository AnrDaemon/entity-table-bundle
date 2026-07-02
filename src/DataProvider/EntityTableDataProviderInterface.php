<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Общий интерфейс, задающий требования к провайдеру данных для таблицы сущностей.
 */
interface EntityTableDataProviderInterface
{
    /**
     * Класс сущностей, отображаемых в таблице.
     *
     * @return class-string
     */
    public function getEntityClass(): string;

    /**
     * Метод тонкой настройки: устанавливает критерии выборки данных для таблицы из общей совокупности.
     */
    public function withScope(Criteria $scope): self;

    /**
     * Метод тонкой настройки: устанавливает порядок сортировки данных.
     */
    public function withOrder(Criteria|array $order): self;

    /**
     * Метод тонкой настройки: устанавливает число элементов на одной странице данных.
     */
    public function withPageSize(int $size): self;

    /**
     * Общее количество элементов во всей таблице.
     */
    public function getTotalDataCount(): int;

    /**
     * Коллекция данных для отображения на конкретной странице.
     */
    public function getDataByPage(int $page = 1): Collection;
}
