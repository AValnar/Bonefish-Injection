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

use Bonefish\Injection\Exceptions\InvalidArgumentException;
use Bonefish\Injection\Exceptions\RuntimeException;
use Bonefish\Injection\Resolver\ResolverInterface;
use Bonefish\Reflection\Meta\ClassMeta;

interface ContainerInterface
{
    /**
     * Fetch a service with supplied parameters.
     * This method will call create for a new service and store them for later use.
     *
     * @param string $className
     * @param array $parameters
     * @return object
     * @throws RuntimeException
     */
    public function get($className, array $parameters = []);

    /**
     * Check if there is an instance in the container
     *
     * @param string $className
     * @param array $parameters
     * @return bool
     */
    public function has($className, array $parameters = []);

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
    public function create($className, array $parameters = []);

    /**
     * Add a resolver
     *
     * @param ResolverInterface $resolver
     * @param int $priority                 Higher priority means it will be used before one
     *                                      with lower priority.
     */
    public function addResolver(ResolverInterface $resolver, $priority = 0);


    /**
     * Add an already created service to the container.
     *
     * @param $object
     * @throws InvalidArgumentException
     */
    public function add($object);
}