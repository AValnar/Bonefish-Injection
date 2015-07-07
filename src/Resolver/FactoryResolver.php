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


final class FactoryResolver extends AbstractResolver implements ResolverInterface
{
    /**
     * @var string
     */
    private $factorySuffix = 'Factory';

    /**
     * @var string
     */
    private $factoryNamespace = 'Factory';

    /**
     * @var array
     */
    private $hasFactory = [];

    /**
     * Resolve a given class name.
     *
     * @param string $className
     * @return string
     */
    public function resolve($className)
    {
        if (!$this->canResolve($className)) {
            return $className;
        }

        $parts = explode('\\', $className);
        $class = array_pop($parts);

        $factoryName = $class . $this->factorySuffix;

        $resolvedClass = implode('\\', $parts) . '\\' . $this->factoryNamespace . '\\' . $factoryName;

        $this->hasFactory[$className] = class_exists($resolvedClass);

        if ($this->hasFactory[$className]) {
            return $resolvedClass;
        }

        return $className;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function canResolve($className)
    {
        if (isset($this->hasFactory[$className])) {
            return $this->hasFactory[$className];
        }

        return true;
    }
}