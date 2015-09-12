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


use Bonefish\Injection\Container\ContainerInterface;
use Bonefish\Injection\Proxy;
use Prophecy\Prophecy\ObjectProphecy;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Proxy
     */
    public $sut;

    /**
     * @var ContainerInterface|ObjectProphecy
     */
    public $container;

    /**
     * @var \stdClass
     */
    public $parent;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->parent = new ParentClass();
        $this->sut = new Proxy(
            DependencyClass::class,
            'testProp',
            $this->parent,
            $this->container->reveal(),
            []
        );
    }

    public function testCall()
    {
        $dependency = new DependencyClass();
        $this->container->get(DependencyClass::class, [])->willReturn($dependency);

        $this->assertThat(
            $this->sut->test('foo'),
            $this->equalTo('foo')
        );

        $this->assertThat(
            $this->parent->testProp,
            $this->equalTo($dependency)
        );

    }

}

class ParentClass
{
    /**
     * @var \stdClass
     */
    public $testProp;
}

class DependencyClass
{
    public function test($foo)
    {
        return $foo;
    }
}


