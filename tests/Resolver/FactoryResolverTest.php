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
 * @date       18.08.2015
 */

namespace Bonefish\Tests\Injection\Resolver;


use Bonefish\Injection\Resolver\FactoryResolver;

/**
 * @property FactoryResolver $sut
 */
class FactoryResolverTest extends AbstractResolverTest
{

    public function setUp()
    {
        $this->sut = new FactoryResolver();
    }

    public function classProvider()
    {
        return [
            ['\Foo\Bar\Baz', '\Foo\Bar\Baz', false],
            ['\Foo\Bar\Test', '\Foo\Bar\Factory\TestFactory', true]
        ];
    }

    /**
     * @dataProvider classProvider
     * @param string $originalClass
     * @param string $factoryClass
     * @param bool $canResolve
     */
    public function testCanResolve($originalClass, $factoryClass, $canResolve)
    {
        $this->assertThat($this->sut->canResolve($originalClass), $this->equalTo($canResolve));

    }

    /**
     * @dataProvider classProvider
     * @param string $originalClass
     * @param string $factoryClass
     */
    public function testResolve($originalClass, $factoryClass)
    {
        $this->assertThat($this->sut->resolve($originalClass), $this->equalTo($factoryClass));
    }


}

namespace Foo\Bar\Factory;

class TestFactory
{
}