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

namespace Bonefish\Injection\Container;

use Bonefish\Injection\PropertyInjector;
use Bonefish\Reflection\Meta\ClassMeta;
use Bonefish\Reflection\ReflectionService;

class PropertyInjectionContainer extends Container
{

    /**
     * @var PropertyInjector
     */
    protected $propertyInjector;

    /**
     * @param ReflectionService $reflectionService
     * @param PropertyInjector $propertyInjector
     */
    public function __construct(ReflectionService $reflectionService, PropertyInjector $propertyInjector)
    {
        parent::__construct($reflectionService);
        $this->propertyInjector = $propertyInjector;
        $this->propertyInjector->setContainer($this);
        $this->add($propertyInjector);
    }

    /**
     * @param string $className
     * @param array $parameters
     * @param ClassMeta $classMeta
     * @return object
     */
    protected function createObject($className, array $parameters, ClassMeta $classMeta)
    {
        $object = parent::createObject($className, $parameters, $classMeta);

        $this->propertyInjector->performInjections($object, $classMeta);

        if (method_exists($object, '__init')) {
            $object->__init();
        }

        return $object;
    }

}