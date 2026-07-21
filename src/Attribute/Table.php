<?php

declare(strict_types=1);

namespace SprintF\Bundle\EntityTable\Attribute;

use Doctrine\Common\Collections\Criteria;
use SprintF\Metadata\Mapping\Attribute\MetadataAttribute;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Table extends MetadataAttribute
{
    public readonly TranslatableInterface $label;

    public function __construct(
        TranslatableInterface|string $label = '',
        public readonly Criteria $initialOrder = new Criteria(),
        public readonly array $groups = [MetadataAttribute::DEFAULT_GROUP],
    ) {
        if (is_string($label)) {
            $this->label = new TranslatableMessage($label);
        } else {
            $this->label = $label;
        }
    }

    public function getKey(): string
    {
        return 'table';
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}
