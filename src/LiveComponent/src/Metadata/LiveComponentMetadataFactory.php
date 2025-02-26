<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Metadata;

use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\ComponentFactory;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @internal
 */
class LiveComponentMetadataFactory implements ResetInterface
{
    /** @var LiveComponentMetadata[] */
    private array $liveComponentMetadata = [];

    public function __construct(
        private ComponentFactory $componentFactory,
        private TypeResolverInterface $typeResolver,
    ) {
    }

    public function getMetadata(string $name): LiveComponentMetadata
    {
        if (isset($this->liveComponentMetadata[$name])) {
            return $this->liveComponentMetadata[$name];
        }

        $componentMetadata = $this->componentFactory->metadataFor($name);

        $reflectionClass = new \ReflectionClass($componentMetadata->getClass());
        $livePropsMetadata = $this->createPropMetadatas($reflectionClass);

        return $this->liveComponentMetadata[$name] = new LiveComponentMetadata($componentMetadata, $livePropsMetadata);
    }

    /**
     * @return LivePropMetadata[]
     *
     * @internal
     */
    public function createPropMetadatas(\ReflectionClass $class): array
    {
        $metadatas = [];

        foreach (self::propertiesFor($class) as $property) {
            if (!$attribute = $property->getAttributes(LiveProp::class)[0] ?? null) {
                continue;
            }

            if (isset($metadatas[$propertyName = $property->getName()])) {
                // property name was already used
                continue;
            }

            $metadatas[$propertyName] = $this->createLivePropMetadata($class->getName(), $propertyName, $property, $attribute->newInstance());
        }

        return array_values($metadatas);
    }

    public function createLivePropMetadata(string $className, string $propertyName, \ReflectionProperty $property, LiveProp $liveProp): LivePropMetadata
    {
        try {
            $type = $this->typeResolver->resolve($property);
        } catch (UnsupportedException) {
            $type = null;
        }

        if ($type instanceof UnionType && !$type instanceof NullableType || $type instanceof IntersectionType) {
            throw new \LogicException(\sprintf('Union or intersection types are not supported for LiveProps. You may want to change the type of property "%s" in "%s".', $propertyName, $className));
        }

        return new LivePropMetadata($property->getName(), $liveProp, $type);
    }

    /**
     * @return iterable<\ReflectionProperty>
     */
    private static function propertiesFor(\ReflectionClass $class): iterable
    {
        foreach ($class->getProperties() as $property) {
            yield $property;
        }

        if ($parent = $class->getParentClass()) {
            yield from self::propertiesFor($parent);
        }
    }

    public function reset(): void
    {
        $this->liveComponentMetadata = [];
    }
}
