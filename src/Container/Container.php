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
 * @date       14.05.2015
 */

namespace Bonefish\Injection\Container;


use Bonefish\Injection\Annotations\Inject;
use Bonefish\Injection\Exceptions\InvalidArgumentException;
use Bonefish\Injection\Exceptions\RuntimeException;
use Bonefish\Injection\Resolver\ResolverInterface;
use Bonefish\Reflection\Meta\ClassMeta;
use Bonefish\Reflection\ReflectionService;

class Container implements ContainerInterface
{

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var array
     */
    protected $prioritizedResolvers = [];

    /**
     * @param ReflectionService $reflectionService
     */
    public function __construct(ReflectionService $reflectionService) {
        $this->reflectionService = $reflectionService;
        $this->add($reflectionService);
    }

    /**
     * Fetch a service with supplied parameters.
     * This method will call create for a new service and store them for later use.
     *
     * @param string $className
     * @param array $parameters
     * @return object
     * @throws RuntimeException
     */
    public function get($className, array $parameters = [])
    {
        if ($this->injectSelf($className)) {
            return $this;
        }

        $parameterKey = $this->getParameterStoreKey($parameters);

        $className = $this->resolve($className);

        if (!isset($this->services[$className][$parameterKey])) {
            $this->services[$className][$parameterKey] = $this->create($className, $parameters);
        }

        return $this->services[$className][$parameterKey];
    }

    /**
     * Check if there is an instance in the container
     *
     * @param string $className
     * @param array $parameters
     * @return bool
     */
    public function has($className, array $parameters = [])
    {
        if ($this->injectSelf($className)) {
            return true;
        }

        $parameterKey = $this->getParameterStoreKey($parameters);

        $className = $this->resolve($className);

        return isset($this->services[$className][$parameterKey]);
    }

    /**
     * Create a class with supplied parameters.
     *
     * Before a class is created the container will first check for a suitable resolver.
     *
     * If an invalid class name was given, e.g. an interface without registered resolver,
     * throw a RuntimeException.
     *
     * @param string $className
     * @param array $parameters
     * @return object
     * @throws RuntimeException
     */
    public function create($className, array $parameters = [])
    {
        if ($this->injectSelf($className)) {
            return $this;
        }

        $className = $this->resolve($className);

        $classMeta = $this->reflectionService->getClassMetaReflection($className);

        if ($classMeta->isInterface()) {
            throw new RuntimeException('Tried to create an interface ( ' . $className . ' ) without registered implementation.');
        }

        $object = $this->createObject($className, $parameters, $classMeta);

        return $object;
    }

    /**
     * Add a resolver
     *
     * @param ResolverInterface $resolver
     * @param int $priority                 Higher priority means it will be used before one
     *                                      with lower priority.
     */
    public function addResolver(ResolverInterface $resolver, $priority = 0)
    {
        if (!isset($this->prioritizedResolvers[$priority])) {
            $this->prioritizedResolvers[$priority] = [];
        }

        array_push($this->prioritizedResolvers[$priority], $resolver);
        krsort($this->prioritizedResolvers);
    }

    /**
     * Add an already created service to the container.
     *
     * @param $object
     * @throws InvalidArgumentException
     */
    public function add($object)
    {
        $className = '\\' . get_class($object);

        if ($className === Container::class) {
            throw new InvalidArgumentException('Tried to add a container instance');
        }

        $parameterKey = $this->getParameterStoreKey([]);

        if (isset($this->services[$className][$parameterKey])) {
            throw new InvalidArgumentException('Tried to add a service instance which already exists');
        }

        $this->services[$className][$parameterKey] = $object;
    }

    /**
     * Get a key to properly store parametrized services
     *
     * @param array $parameters
     * @return string
     */
    protected function getParameterStoreKey(array $parameters)
    {
        return implode('|', $parameters);
    }


    /**
     * @param string $className
     * @param array $parameters
     * @param ClassMeta $classMeta
     * @return object
     */
    protected function createObject($className, array $parameters, ClassMeta $classMeta)
    {
        if (!empty($parameters)) {
            return new $className(...$parameters);
        }

        $constructorMethod = $classMeta->getMethod('__construct');

        if ($constructorMethod === false || $constructorMethod->getAnnotation(Inject::class) === false) {
            return new $className();
        }

        $constructorInjections = $this->getConstructorInjections($classMeta);
        return new $className(...$constructorInjections);
    }

    /**
     * @param ClassMeta $classMeta
     * @return array
     */
    protected function getConstructorInjections(ClassMeta $classMeta)
    {
        $dependencies = [];
        $parameters = $classMeta->getMethod('__construct')->getParameters();

        foreach ($parameters as $parameter)
        {
            $type = $parameter->getType();
            if ($type === 'array' || $type === 'mixed') continue;

            $dependencies[] = $this->get($type);
        }

        return $dependencies;
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function injectSelf($className)
    {
        return (ltrim($className, '\\') === Container::class);
    }

    /**
     * Pass class name through resolvers
     * TODO: Maybe create a chained resolver instead ?
     *
     * @param string $className
     * @return string
     */
    protected function resolve($className)
    {
        foreach($this->prioritizedResolvers as $priority => $resolvers) {
            /** @var ResolverInterface $resolver */
            foreach($resolvers as $resolver) {
                if ($resolver->canResolve($className)) {
                    $className = $resolver->resolve($className);
                    if ($resolver->stopPropagation()) return $className;
                }
            }
        }

        return $className;
    }

}