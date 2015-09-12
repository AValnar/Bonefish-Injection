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

    const FACTORY_SUFFIX = 'Factory';
    const FACTORY_NAMESPACE = 'Factory';

    /**
     * @var array
     */
    private $hasFactory = [];

    /**
     * @param string $className
     * @return string
     */
    private function getFactoryName($className)
    {
        $parts = explode('\\', $className);
        $class = array_pop($parts);

        $factoryName = $class . self::FACTORY_SUFFIX;

        return implode('\\', $parts) . '\\' . self::FACTORY_NAMESPACE . '\\' . $factoryName;
    }

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

        return $this->getFactoryName($className);
    }

    /**
     * @param string $className
     * @return bool
     */
    public function canResolve($className)
    {
        if (!isset($this->hasFactory[$className])) {
            $factoryName = $this->getFactoryName($className);
            $this->hasFactory[$className] = class_exists($factoryName);
        }

        return $this->hasFactory[$className];
    }
}