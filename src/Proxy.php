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


class Proxy
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $property;

    /**
     * @var object
     */
    protected $parent;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param string $className
     * @param string $property
     * @param object $parent
     * @param array $parameters
     * @param ContainerInterface $container
     */
    public function __construct($className, $property, $parent, $container, array $parameters = [])
    {
        $this->className = $className;
        $this->property = $property;
        $this->parent = $parent;
        $this->container = $container;
        $this->parameters = $parameters;
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments = [])
    {
        $dependency = $this->container->get($this->className, $this->parameters);
        $this->parent->{$this->property} = $dependency;

        return call_user_func_array([$this->parent->{$this->property}, $name], $arguments);
    }


    public function __sleep()
    {
        // Break the proxy, because objects with proxies in them should most likely not be serialised anyway
        return ['className'];
    }
}