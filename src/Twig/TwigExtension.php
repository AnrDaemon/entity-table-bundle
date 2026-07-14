<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Twig;

use SprintF\Bundle\EntityTable\DataProvider\EntityTableDataProviderInterface;
use SprintF\Bundle\EntityTable\DataProvider\PersistentCollectionDataProvider;
use SprintF\Bundle\EntityTable\DataProvider\QueryDataProvider;
use Twig\Attribute\AsTwigFunction;

class TwigExtension
{
    #[AsTwigFunction('provider')]
    public function getDataProviderForData($data): EntityTableDataProviderInterface
    {
        foreach ([
            PersistentCollectionDataProvider::class,
            QueryDataProvider::class,
        ] as $provider) {
            if ($provider::supports($data)) {
                return new $provider($data);
            }
        }

        throw new \InvalidArgumentException('Unsupported data provider.');
    }
}
