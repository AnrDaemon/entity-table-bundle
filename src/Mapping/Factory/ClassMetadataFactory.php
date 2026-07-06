<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Mapping\Factory;

use SprintF\Bundle\EntityTable\Mapping\Loader\AttributeLoader;
use SprintF\Metadata\Mapping\Factory\ClassMetadataFactory as ClassMetadataFactoryAbstract;
use SprintF\Metadata\Mapping\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ClassMetadataFactory extends ClassMetadataFactoryAbstract
{
    public function __construct(
        #[Autowire(service: AttributeLoader::class)]
        protected readonly LoaderInterface $loader,
    ) {
    }
}
