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
 * @date       20.08.2015
 */

namespace Bonefish\Tests\Injection;


use Bonefish\Injection\Annotations\Inject;
use Bonefish\Injection\Container\ContainerInterface;
use Bonefish\Injection\PropertyInjector;
use Bonefish\Injection\Proxy;
use Bonefish\Reflection\Annotations\Variable;
use Bonefish\Reflection\Meta\ClassMeta;
use Bonefish\Reflection\Meta\PropertyMeta;
use Doctrine\Common\Cache\Cache;
use Prophecy\Prophecy\ObjectProphecy;

class PropertyInjectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyInjector
     */
    public $sut;

    /**
     * @var Cache|ObjectProphecy
     */
    public $cache;

    /**
     * @var ClassMeta|ObjectProphecy
     */
    public $classMeta;

    public function setUp()
    {
        $this->cache = $this->prophesize(Cache::class);
        $this->classMeta = $this->prophesize(ClassMeta::class);
        $this->sut = new PropertyInjector($this->cache->reveal());
    }

    /**
     * @expectedException \Bonefish\Injection\Exceptions\RuntimeException
     */
    public function testPerformInjectionsWithoutContainer()
    {
        $this->sut->performInjections(null, $this->classMeta->reveal());
    }

    public function testGetPropertyInjectionProperties()
    {
        $properties = $this->getDefaultTestProperties();
        $this->classMeta->getProperties()->willReturn($properties);

        $expectedValue = [
            [
                'className' => Foo::class,
                'parameters' => [],
                'property' => $properties['Foo']
            ],
            [
                'className' => Baz::class,
                'parameters' => [['Foo', ['Bar']]],
                'property' => $properties['Baz']
            ]
        ];


        $this->assertThat(
            $this->sut->getPropertyInjectionProperties($this->classMeta->reveal()),
            $this->equalTo($expectedValue)
        );
    }

    /**
     * @expectedException \Bonefish\Injection\Exceptions\RuntimeException
     */
    public function testGetPropertyInjectionPropertiesForInvalidClass()
    {
        $properties = [$this->createPropertyMeta(true, false, true)];
        $this->classMeta->getProperties()->willReturn($properties);
        $this->sut->getPropertyInjectionProperties($this->classMeta->reveal());
    }

    /**
     * @depends testGetPropertyInjectionProperties
     */
    public function testPerformInjections()
    {
        $object = new \stdClass();
        $container = $this->prophesize(ContainerInterface::class);
        $this->classMeta->getName()->willReturn('Foo');

        $cacheKey = $this->sut->getCacheKey('Foo');
        $this->cache->fetch($cacheKey)->willReturn(null);

        $properties = $this->getDefaultTestProperties();
        $this->classMeta->getProperties()->willReturn($properties);

        $fooService = new Foo;
        $container->has(Foo::class, [])->willReturn(true);
        $container->get(Foo::class, [])->willReturn($fooService);
        $container->has(Baz::class, [['Foo', ['Bar']]])->willReturn(false);

        $revealedContainer = $container->reveal();
        $revealedClassMeta = $this->classMeta->reveal();

        $this->cache->save($cacheKey, $this->sut->getPropertyInjectionProperties($revealedClassMeta))->shouldBeCalled();

        $proxy = new Proxy(Baz::class, 'baz', $object, $revealedContainer, [['Foo', ['Bar']]]);

        $this->sut->setContainer($revealedContainer);
        $this->sut->performInjections($object, $revealedClassMeta);

        $this->assertThat($object->foo, $this->equalTo($fooService));
        $this->assertThat($object->baz, $this->equalTo($proxy));
    }

    /**
     * @return PropertyMeta[]
     */
    protected function getDefaultTestProperties()
    {
        return [
            'Foo' => $this->createPropertyMeta(true, true, true, [], Foo::class, 'foo'),
            'Cake' => $this->createPropertyMeta(false, true, false, [], 'Cake', 'cake'),
            'Bar' => $this->createPropertyMeta(false, true, true, [], 'Bar', 'bar'),
            'Baz' => $this->createPropertyMeta(true, true, true, ['Foo', ['Bar']], Baz::class, 'baz')
        ];
    }

    /**
     * Create a PropertyMeta prophecy
     *
     * @param bool $injectAnnotation
     * @param bool $varAnnotation
     * @param bool $public
     * @param array $injectParameters
     * @param string $varType
     * @param string $propertyName
     * @return PropertyMeta
     */
    protected function createPropertyMeta(
        $injectAnnotation = true,
        $varAnnotation = true,
        $public = true,
        $injectParameters = [],
        $varType = 'mixed',
        $propertyName = ''
    )
    {
        $property = $this->prophesize(PropertyMeta::class);

        if ($injectAnnotation) {
            $_injectAnnotation = $this->prophesize(Inject::class);
            $_injectAnnotation->getParameters()->willReturn($injectParameters);
            $property->getAnnotation(Inject::class)->willReturn($_injectAnnotation->reveal());
        } else {
            $property->getAnnotation(Inject::class)->willReturn(false);
        }

        if ($varAnnotation) {
            $_varAnnotation = $this->prophesize(Variable::class);
            $_varAnnotation->getType()->willReturn($varType);
            $property->getAnnotation(Variable::class)->willReturn($_varAnnotation->reveal());
        } else {
            $property->getAnnotation(Variable::class)->willReturn(false);
        }

        $property->isPublic()->willReturn($public);
        $property->getName()->willReturn($propertyName);

        return $property->reveal();
    }

}

class Foo {}
class Baz {}
