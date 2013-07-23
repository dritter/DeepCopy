<?php

namespace DeepCopy;

use DeepCopy\Filter\Filter;
use DeepCopy\Filter\SetNull;
use ReflectionClass;

/**
 * DeepCopy
 */
class DeepCopy
{
    /**
     * @var array
     */
    private $hashMap = array();

    /**
     * Filters to apply.
     * @var Filter[]
     */
    private $filters = array();

    /**
     * Perform a deep copy of the object.
     * @param object $object
     * @return object
     */
    public function copy($object)
    {
        $this->hashMap = array();

        return $this->recursiveCopy($object);
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    public function setNull($class, $property)
    {
        $this->addFilter(new SetNull($class, $property));
    }

    private function recursiveCopy($object)
    {
        $objectHash = spl_object_hash($object);

        if (isset($this->hashMap[$objectHash])) {
            return $this->hashMap[$objectHash];
        }

        $newObject = clone $object;

        $this->hashMap[$objectHash] = $newObject;

        // Apply the filters
        foreach ($this->filters as $filter) {
            if ($filter->applies($newObject)) {
                $filter->apply($newObject);
            }
        }

        // Clone properties
        $class = new ReflectionClass($object);
        foreach ($class->getProperties() as $property) {
            $property->setAccessible(true);
            $propertyValue = $property->getValue($object);
            if (is_object($propertyValue)) {
                $property->setValue($object, $this->recursiveCopy($propertyValue));
            } elseif (is_array($propertyValue)) {
                $newPropertyValue = array();
                foreach ($propertyValue as $i => $item) {
                    $newPropertyValue[$i] = $this->recursiveCopy($item);
                }
                $property->setValue($object, $newPropertyValue);
            }
        }

        return $newObject;
    }
}
