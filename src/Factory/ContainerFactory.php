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
 * @date       29.05.2015
 */

namespace Bonefish\Injection\Factory;

use Bonefish\Injection\Container;
use Bonefish\Injection\ContainerInterface;
use Bonefish\Injection\FactoryInterface;
use Bonefish\Reflection\ClassNameResolver;
use Bonefish\Reflection\Factory\ReflectionServiceFactory;
use Bonefish\Reflection\ReflectionService;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\Cache;

final class ContainerFactory implements FactoryInterface
{
    /**
     * Return an object with fully injected dependencies
     *
     * @param array $parameters
     * @return Container
     */
    public function create(array $parameters = [])
    {
        if (isset($parameters['cache']) && $parameters['cache'] instanceof Cache) {
            $cache = $parameters['cache'];
        } else {
            $cache = new ApcCache();
        }

        $reflectionServiceFactory = new ReflectionServiceFactory();
        $reflectionService = $reflectionServiceFactory->create($parameters);
        $classNameRevolver = new ClassNameResolver();

        $container = new Container($reflectionService, $classNameRevolver, $cache);

        $container->add($cache);
        $container->add($reflectionService);
        $container->add($classNameRevolver);

        $container->setInterfaceImplementation(Container::class, ContainerInterface::class);

        return $container;
    }

}