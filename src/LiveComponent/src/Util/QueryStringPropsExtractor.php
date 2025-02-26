<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\UX\LiveComponent\Exception\HydrationException;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadata;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;

/**
 * @author Nicolas Rigaud <squrious@protonmail.com>
 *
 * @internal
 */
final class QueryStringPropsExtractor
{
    public function __construct(private readonly LiveComponentHydrator $hydrator)
    {
    }

    /**
     * Extracts relevant query parameters from the current URL and hydrates them.
     */
    public function extract(Request $request, LiveComponentMetadata $metadata, object $component): array
    {
        $query = $request->query->all();

        if (empty($query)) {
            return [];
        }
        $data = [];

        $typeIsScalar = function (Type $type) use (&$typeIsScalar): bool {
            return match (true) {
                $type instanceof BuiltinType => $type->getTypeIdentifier()->isScalar(),
                $type instanceof CompositeTypeInterface => $type->composedTypesAreSatisfiedBy($typeIsScalar),
                $type instanceof WrappingTypeInterface => $type->wrappedTypeIsSatisfiedBy($typeIsScalar),
                default => false,
            };
        };

        foreach ($metadata->getAllLivePropsMetadata($component) as $livePropMetadata) {
            if ($queryMapping = $livePropMetadata->urlMapping()) {
                $frontendName = $livePropMetadata->calculateFieldName($component, $livePropMetadata->getName());
                if (null !== ($value = $query[$queryMapping->as ?? $frontendName] ?? null)) {
                    if ('' === $value && null !== $livePropMetadata->getType() && (!$livePropMetadata->getType()->isSatisfiedBy($typeIsScalar))) {
                        // Cast empty string to empty array for objects and arrays
                        $value = [];
                    }

                    try {
                        $hydratedValue = $this->hydrator->hydrateValue($value, $livePropMetadata, $component);

                        if ($this->isValueTypeConsistent($hydratedValue, $livePropMetadata)) {
                            // Only set data if hydrated value type is consistent with prop metadata type
                            $data[$livePropMetadata->getName()] = $hydratedValue;
                        }
                    } catch (HydrationException) {
                        // Skip hydration errors (e.g. with objects)
                    }
                }
            }
        }

        return $data;
    }

    private function isValueTypeConsistent(mixed $value, LivePropMetadata $livePropMetadata): bool
    {
        if (null === $type = $livePropMetadata->getType()) {
            return true;
        }

        if ($type->isIdentifiedBy(TypeIdentifier::MIXED)) {
            return true;
        }

        if ($type->isNullable() && null === $value) {
            return true;
        }

        while ($type instanceof WrappingTypeInterface) {
            $type = $type->getWrappedType();
        }

        if ($type instanceof ObjectType) {
            $className = $type->getClassName();

            return $value instanceof $className;
        }

        return ('is_'.$type)($value);
    }
}
