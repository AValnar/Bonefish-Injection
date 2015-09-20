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

namespace Bonefish\Injection\Resolver;


final class InterfaceResolver implements ResolverInterface
{

    /**
     * @var array
     */
    private $implementations = [];

    /**
     * @param array $implementations
     */
    public function __construct($implementations = [])
    {
        $this->implementations = $implementations;
    }

    /**
     * @param string $interface
     * @param string $implementation
     */
    public function addImplementation($interface, $implementation)
    {
        $trimmed = ltrim($interface, '\\');
        $this->implementations[$trimmed] = $implementation;
    }

    /**
     * Resolve a given class name.
     *
     * If the resolved class does not exist return the original class.
     *
     * @param string $className
     * @return string
     */
    public function resolve($className)
    {
        $trimmed = ltrim($className, '\\');

        if (!$this->canResolve($trimmed)) return $className;

        return $this->implementations[$trimmed];
    }

    /**
     * @param string $className
     * @return bool
     */
    public function canResolve($className)
    {
        return isset($this->implementations[$className]);
    }

    /**
     * Stop propagation ?
     *
     * @return bool
     */
    public function stopPropagation()
    {
        return true;
    }


}