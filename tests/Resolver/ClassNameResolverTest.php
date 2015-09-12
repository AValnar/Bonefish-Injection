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


use Bonefish\Injection\Resolver\ClassNameResolver;
use Bonefish\Reflection\ClassNameResolverInterface;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @property ClassNameResolver $sut
 */
class ClassNameResolverTest extends AbstractResolverTest
{

    /**
     * @var ClassNameResolverInterface|ObjectProphecy
     */
    public $classNameResolver;

    const TEST_CLASS = 'Foo';

    public function setUp()
    {
        $this->classNameResolver = $this->prophesize(ClassNameResolverInterface::class);
        $this->sut = new ClassNameResolver($this->classNameResolver->reveal());
    }

    public function testResolve()
    {
        $this->classNameResolver->resolveClassName(self::TEST_CLASS)->willReturn(self::TEST_CLASS);
        $this->assertThat($this->sut->resolve(self::TEST_CLASS), $this->equalTo(self::TEST_CLASS));
    }

    public function testCanResolve()
    {
        $this->assertThat($this->sut->canResolve(self::TEST_CLASS), $this->isTrue());
    }


}
