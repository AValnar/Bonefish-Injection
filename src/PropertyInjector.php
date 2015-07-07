<?php
/**
 * Copyright (C) 2015  Alexander Schmidt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @author     Alexander Schmidt <mail@story75.com>
 * @copyright  Copyright (c) 2015, Alexander Schmidt
 * @date       07.07.2015
 */

namespace Bonefish\Injection;

use Bonefish\Injection\Annotations\Inject;
use Bonefish\Injection\Container\ContainerInterface;
use Bonefish\Injection\Exceptions\RuntimeException;
use Bonefish\Reflection\Annotations\Variable;
use Bonefish\Reflection\Meta\ClassMeta;
use Bonefish\Reflection\Meta\PropertyMeta;
use Bonefish\Traits\CacheHelperTrait;
use Doctrine\Common\Cache\Cache;

class PropertyInjector
{

    use CacheHelperTrait;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @param Cache $cache
     * @Bonefish\Inject
     */
    public function __construct(Cache $cache) {
        $this->cache = $cache;
        $this->setCachePrefix('bonefish.injection.propertyInjector');
    }

    /**
     * @param ContainerInterface $container
     * @return self
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Perform dependency injections for matching properties of $object.
     *
     * This function will use all properties with an matching inject annotation and inject the class
     * annotated with var via get().
     *
     * You can define injection parameters after the inject annotation as either a string or array.
     * E.g. @Bonefish\Inject foo
     * @Bonefish\Inject(vector=XYZ, key=ABC)
     *
     * All injection have to be lazy if the requested service is not yet created.
     * This means a proxy will instead be inserted which will replace itself.
     *
     * @param object $object
     * @param ClassMeta $classMeta
     * @throws RuntimeException
     */
    public function performInjections($object, ClassMeta $classMeta)
    {
        if (!$this->container instanceof ContainerInterface)
        {
            throw new RuntimeException('You have to set a container in order to perform injections!');
        }

        $properties = null;

        $cacheKey = $this->getCacheKey($classMeta->getName());
        $hit = $this->cache->fetch($cacheKey);

        if ($hit !== false) {
            $properties = $hit;
        }

        if ($properties === null) {
            $properties = $this->getPropertyInjectionProperties($classMeta);
            $this->cache->save($cacheKey, $properties);
        }

        foreach ($properties as $propertyInjection) {
            $this->propertyInjection(
                $propertyInjection['className'],
                $propertyInjection['parameters'],
                $object,
                $propertyInjection['property']
            );
        }
    }

    /**
     * @param ClassMeta $classMeta
     * @throws RuntimeException
     * @return array
     */
    public function getPropertyInjectionProperties(ClassMeta $classMeta)
    {
        $properties = [];

        foreach ($classMeta->getProperties() as $property) {

            if (!$property->isPublic()) {
                continue;
            }
            /** @var Inject $annotation */
            $annotation = $property->getAnnotation(Inject::class);

            if ($annotation === false) {
                continue;
            }
            /** @var Variable $varAnnotation */
            $varAnnotation = $property->getAnnotation(Variable::class);
            if ($varAnnotation === false) {
                throw new RuntimeException('Tried to inject without var annotation.');
            }

            $className = $varAnnotation->getType();

            $parameters = [];

            if (!empty($annotation->getParameters())) {
                $parameters = [$annotation->getParameters()];
            }

            $properties[] = [
                'className' => $className,
                'parameters' => $parameters,
                'property' => $property
            ];

        }

        return $properties;
    }

    /**
     * @param string $className
     * @param array $parameters
     * @param object $object
     * @param PropertyMeta $property
     */
    protected function propertyInjection($className, $parameters, $object, $property)
    {
        $dependency = $this->getServiceOrProxy($className, $property->getName(), $object, $parameters);
        $object->{$property->getName()} = $dependency;
    }

    /**
     * @param string $className
     * @param string $property
     * @param object $parent
     * @param array $parameters
     * @return Proxy|object
     */
    protected function getServiceOrProxy(
        $className,
        $property,
        $parent,
        array $parameters
    )
    {
        if ($this->container->has($className, $parameters)) {
            return $this->container->get($className, $parameters);
        }

        return new Proxy($className, $property, $parent, $this->container, $parameters);
    }
}