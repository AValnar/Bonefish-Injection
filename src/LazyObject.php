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
 * @date       20.09.2015
 */

namespace Bonefish\Injection;


use Bonefish\Injection\Container\ContainerInterface;

final class LazyObject
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $className;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param string $className
     * @param array $parameters
     * @param ContainerInterface $container
     */
    public function __construct($className, ContainerInterface $container, array $parameters = [])
    {
        $this->className = $className;
        $this->container = $container;
        $this->parameters = $parameters;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        $object = $this->container->get($this->className, $this->parameters);

        return call_user_func([$object, $name], ...$arguments);
    }
}