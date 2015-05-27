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

namespace Bonefish\Injection;


use Bonefish\Factory\IFactory;
use Bonefish\Injection\Exceptions\RuntimeException;
use Bonefish\Reflection\ClassNameResolver;
use Bonefish\Reflection\Meta\Annotations\VarAnnotationMeta;
use Bonefish\Reflection\Meta\ClassMeta;
use Bonefish\Reflection\ReflectionService;

class Container implements ContainerInterface
{

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ClassNameResolver
     */
    protected $classNameResolver;

    /**
     * @var array
     */
    protected $services = array();

    /**
     * @var array
     */
    protected $implementations = array();

    /**
     * @var array
     */
    protected $hasFactory = array();

    public function __construct(ReflectionService $reflectionService, ClassNameResolver $classNameResolver)
    {
        $this->reflectionService = $reflectionService;
        $this->classNameResolver = $classNameResolver;
    }

    /**
     * Fetch a service with supplied parameters.
     * This method will call create for a new service and store it for later use.
     *
     * @param $className
     * @param array $parameters
     * @return object
     */
    public function get($className, array $parameters = array())
    {
        if ($this->injectSelf($className)) {
            return $this;
        }

        $parameterKey = $this->getParameterStoreKey($parameters);

        $className = $this->resolveClassName($className);

        if (!isset($this->services[$className][$parameterKey])) {
            $this->services[$className][$parameterKey] = $this->create($className, $parameters);
        }

        return $this->services[$className][$parameterKey];
    }

    /**
     * Create a class with supplied parameters.
     *
     * Before a class is created the container will first check if the requested class is an interface and will try to
     * find an registered implementation. If no implementation was found a RuntimeException is thrown.
     *
     * After the implementation has been resolved and before the object is created the container will check if a factory
     * for this class exists. This Factory has to implement the interface \Bonefish\Factory\IFactory.
     * The name is resolved as follows \Full\Name\Space\Class => \Full\Name\Space\FACTORY_NAMESPACE\ClassFACTORY_SUFFIX
     * E.g. \Bonefish\Core\Environment => \Bonefish\Core\Factory\EnvironmentFactory
     * When a factory exists get() will be called to retrieve an instance and will be used to create an instance.
     *
     * After the class has been created performInjections() is called on the object.
     * After performInjections() was called the method __init() will be called on the object
     * if the method exists.
     *
     * @param $className
     * @param array $parameters
     * @return object
     * @throws RuntimeException
     */
    public function create($className, array $parameters = array())
    {
        if ($this->injectSelf($className)) {
            return $this;
        }

        $className = $this->resolveClassName($className);

        $classMeta = $this->reflectionService->getClassMetaReflection($className);

        if ($classMeta->isInterface()) {
            throw new RuntimeException('Tried to create an interface ( ' . $className . ' ) without registered implementation.');
        }

        $object = $this->createObject($className, $parameters);

        $this->performInjections($object, $classMeta);

        if (method_exists($object, '__init')) {
            $object->__init();
        }

        return $object;
    }

    /**
     * Set a class to be used for a specific interface
     *
     * @param string $interface
     * @param string $implementation The fully qualified class name of the implementation
     */
    public function setInterfaceImplementation($implementation, $interface)
    {
        $interface = $this->classNameResolver->resolveClassName($interface);
        $implementation = $this->classNameResolver->resolveClassName($implementation);

        $this->implementations[$interface] = $implementation;
    }

    /**
     * Return an implementation for an interface or return the name again.
     *
     * @param string $interface
     * @return string
     */
    protected function resolveInterface($interface)
    {
        if (isset($this->implementations[$interface])) {
            return $this->implementations[$interface];
        }

        return $interface;
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
    public function performInjections($object, ClassMeta $classMeta = null)
    {
        if ($classMeta === null) {
            $classMeta = $this->reflectionService->getClassMetaReflection(get_class($object));
        }

        foreach ($classMeta->getProperties() as $property) {
            if ($property->isPublic()) {
                foreach (self::INJECT_ANNOTATIONS as $injectAnnotation) {
                    $annotation = $property->getAnnotation($injectAnnotation);

                    if ($annotation !== false) {
                        /** @var VarAnnotationMeta $varAnnotation */
                        $varAnnotation = $property->getAnnotation('var');
                        if ($varAnnotation === false) {
                            throw new RuntimeException('Tried to inject without var annotation.');
                        }

                        $className = $varAnnotation->getClassName();

                        $parameters = array();

                        if ($annotation->getParameter()->hasDefaultValue()) {
                            $parameters = array($annotation->getParameter()->getDefaultValue());
                        }

                        $dependency = $this->getServiceOrProxy($className, $property->getName(), $object, $parameters);
                        $object->{$property->getName()} = $dependency;

                        break;
                    }
                }
            }
        }
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
     * @return string
     */
    protected function resolveClassName($className)
    {
        $className = $this->classNameResolver->resolveClassName($className);

        return $this->resolveInterface($className);
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getFactoryClassName($className)
    {
        $parts = explode('\\', $className);
        $class = array_pop($parts);

        $factoryName = $class . self::FACTORY_SUFFIX;

        return implode('\\', $parts) . '\\' . self::FACTORY_NAMESPACE . '\\' . $factoryName;
    }

    /**
     * Return factory object or false if none exists
     *
     * @param string $className
     * @return IFactory|bool
     */
    protected function getFactory($className)
    {
        if (!isset($this->hasFactory[$className])) {
            $factoryClassName = $this->getFactoryClassName($className);

            if (class_exists($factoryClassName)) {
                $this->hasFactory[$className] = $factoryClassName;
            } else {
                $this->hasFactory[$className] = false;

                return false;
            }
        }

        if ($this->hasFactory[$className] === false) {
            return false;
        }

        return $this->get($this->hasFactory[$className]);
    }

    /**
     * @param string $className
     * @param array $parameters
     * @return object
     */
    protected function createObject($className, array $parameters)
    {
        $factory = $this->getFactory($className);

        if ($factory !== false) {
            $object = $factory->create($parameters);
        } else {
            if (empty($parameters)) {
                $object = new $className();
            } else {
                // can't avoid a real reflection here
                $reflection = $this->reflectionService->getClassReflection($className);
                $object = $reflection->newInstanceArgs($parameters);
            }
        }

        return $object;
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
     * @param string $className
     * @param string $property
     * @param object $parent
     * @param array $parameters
     * @return Proxy|object
     */
    protected function getServiceOrProxy($className, $property, $parent, array $parameters)
    {
        $parameterKey = $this->getParameterStoreKey($parameters);

        if (isset($this->services[$className][$parameterKey])) {
            return $this->get($className, $parameters);
        }

        return new Proxy($className, $property, $parent, $this, $parameters);
    }
}