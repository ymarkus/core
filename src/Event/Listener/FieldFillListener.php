<?php

declare(strict_types=1);

namespace Bolt\Event\Listener;

use Bolt\Configuration\Content\FieldType;
use Bolt\Entity\Field;
use Bolt\Entity\Field\CollectionField;
use Bolt\Entity\Field\SetField;
use Bolt\Repository\FieldRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;

class FieldFillListener
{
    /** @var FieldRepository */
    private $fields;

    /** @var ContentFillListener */
    private $cfl;

    public function __construct(FieldRepository $fields, ContentFillListener $cfl)
    {
        $this->fields = $fields;
        $this->cfl = $cfl;
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof Field) {
            $this->fillField($entity);
        }

        if ($entity instanceof CollectionField) {
            $this->fillCollection($entity);
        }

        if ($entity instanceof SetField) {
            $this->fillSet($entity);
        }
    }

    public function fillField(Field $field): void
    {
        // Fill in the definition of the field
        $parents = $this->getParents($field);
        $this->cfl->fillContent($field->getContent());
        $contentDefinition = $field->getContent()->getDefinition();
        $field->setDefinition($field->getName(), FieldType::factory($field->getName(), $contentDefinition, $parents));
    }

    private function getParents(Field $field): array
    {
        $parents = [];

        if ($field->hasParent()) {
            $parents = $this->getParents($field->getParent());
            $parents[] = $field->getParent()->getName();
        }

        return $parents;
    }

    public function fillSet(SetField $entity): void
    {
        $fields = $this->fields->findAllByParent($entity);
        $entity->setValue($fields);
    }

    public function fillCollection(CollectionField $entity): void
    {
        $fields = $this->intersectFieldsAndDefinition($this->fields->findAllByParent($entity), $entity->getDefinition());
        $entity->setValue($fields);
    }

    private function intersectFieldsAndDefinition(array $fields, FieldType $definition): array
    {
        return collect($fields)->filter(function (Field $field) use ($definition) {
            return $definition->get('fields') && $definition->get('fields')->has($field->getName());
        })->toArray();
    }
}
