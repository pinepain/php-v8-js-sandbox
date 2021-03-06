<?php declare(strict_types=1);

/*
 * This file is part of the pinepain/js-sandbox PHP library.
 *
 * Copyright (c) 2016-2017 Bogdan Padalko <pinepain@gmail.com>
 *
 * Licensed under the MIT license: http://opensource.org/licenses/MIT
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source or visit
 * http://opensource.org/licenses/MIT
 */


namespace Pinepain\JsSandbox\Tests\Specs\Builder;


use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Pinepain\JsSandbox\Decorators\DecoratorSpecBuilderInterface;
use Pinepain\JsSandbox\Decorators\DecoratorSpecInterface;
use Pinepain\JsSandbox\Specs\Builder\FunctionSpecBuilder;
use Pinepain\JsSandbox\Specs\Builder\FunctionSpecBuilderInterface;
use Pinepain\JsSandbox\Specs\Builder\ParameterSpecBuilderInterface;
use Pinepain\JsSandbox\Specs\FunctionSpecInterface;
use Pinepain\JsSandbox\Specs\Parameters\ParameterSpecInterface;
use Pinepain\JsSandbox\Specs\ReturnSpec\AnyReturnSpec;
use Pinepain\JsSandbox\Specs\ReturnSpec\VoidReturnSpec;


class FunctionSpecBuilderTest extends TestCase
{
    /**
     * @var FunctionSpecBuilderInterface
     */
    protected $builder;

    /**
     * @var DecoratorSpecBuilderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $decorators_builder;
    /**
     * @var ParameterSpecBuilderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $parameters_builder;

    public function setUp()
    {
        $this->decorators_builder = $this->getMockForAbstractClass(DecoratorSpecBuilderInterface::class);
        $this->parameters_builder = $this->getMockForAbstractClass(ParameterSpecBuilderInterface::class);

        $this->builder = new FunctionSpecBuilder($this->decorators_builder, $this->parameters_builder);
    }

    /**
     * @expectedException \Pinepain\JsSandbox\Specs\Builder\Exceptions\FunctionSpecBuilderException
     * @expectedExceptionMessage Definition must be non-empty string
     */
    public function testBuildingFromEmptyStringShouldThrow()
    {
        $this->builder->build('');
    }

    /**
     * @expectedException \Pinepain\JsSandbox\Specs\Builder\Exceptions\FunctionSpecBuilderException
     * @expectedExceptionMessage Unable to parse definition: 'invalid'
     */
    public function testBuildingFromInvalidStringShouldThrow()
    {
        $this->builder->build('invalid');
    }

    public function testBuildEmptySpec()
    {
        $spec = $this->builder->build('()');
        $this->assertSame([], $spec->getDecorators());
        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
    }

    // Test return type
    public function testBuildingSpecWithVoidReturnType()
    {
        $spec = $this->builder->build('(): void');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertInstanceOf(VoidReturnSpec::class, $spec->getReturn());
    }

    public function testBuildingSpecWithAnyReturnType()
    {
        $spec = $this->builder->build('(): any');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertInstanceOf(AnyReturnSpec::class, $spec->getReturn());
    }

    public function testBuildingSpecWithNoReturnTypeIsTheSameAsWithAnyReturnType()
    {
        $spec = $this->builder->build('()');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertInstanceOf(AnyReturnSpec::class, $spec->getReturn());
    }

    public function testBuildSpecWithParams()
    {
        $this->parameterBuilderShouldBuildOn('one: param', 'two = "default": param', '...params: rest');

        $spec = $this->builder->build('(one: param, two = "default": param, ...params: rest)');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertContainsOnlyInstancesOf(ParameterSpecInterface::class, $spec->getParameters()->getParameters());
        $this->assertCount(3, $spec->getParameters()->getParameters());
    }

    public function testBuildSpecWithNullableParams()
    {
        $this->parameterBuilderShouldBuildOn('one: param', 'two?: param');

        $spec = $this->builder->build('(one: param, two?: param)');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertContainsOnlyInstancesOf(ParameterSpecInterface::class, $spec->getParameters()->getParameters());
        $this->assertCount(2, $spec->getParameters()->getParameters());
    }

    // Test throws spec
    public function testBuildingSpecWithoutThrows()
    {
        $spec = $this->builder->build('()');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);

        $this->assertSame([], $spec->getDecorators());
        $this->assertEmpty($spec->getExceptions()->getThrowSpecs());
    }

    public function testBuildingSpecWithSingleThrows()
    {
        $spec = $this->builder->build('() throws Test');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertCount(1, $spec->getExceptions()->getThrowSpecs());
    }


    public function testBuildingSpecWithMultipleThrows()
    {
        $spec = $this->builder->build('() throws Test, Foo, Bar');

        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertSame([], $spec->getDecorators());
        $this->assertCount(3, $spec->getExceptions()->getThrowSpecs());
    }

    /**
     * @expectedException \Pinepain\JsSandbox\Specs\Builder\Exceptions\FunctionSpecBuilderException
     * @expectedExceptionMessage Invalid return type: 'invalid'
     */
    public function testBuildingSpecWithInvalidReturnTypeStringShouldThrow()
    {
        $this->builder->build('(): invalid');
    }

    public function testBuildSpecWithDecorator()
    {
        $this->decoratorBuilderShouldBuildOn('@test');

        $spec = $this->builder->build('@test ()');
        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertCount(1, $spec->getDecorators());

        $this->assertInstanceOf(DecoratorSpecInterface::class, $spec->getDecorators()[0]);
    }

    public function testBuildSpecWithDashedDecorator()
    {
        $this->decoratorBuilderShouldBuildOn('@inject-context');

        $spec = $this->builder->build('@inject-context (id: string)');
        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertCount(1, $spec->getDecorators());

        $this->assertInstanceOf(DecoratorSpecInterface::class, $spec->getDecorators()[0]);
    }

    public function testBuildSpecWithMultipleDecorators()
    {
        $this->decoratorBuilderShouldBuildOn('@first', '@second');

        $spec = $this->builder->build('@first @second ()');
        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertCount(2, $spec->getDecorators());

        $this->assertInstanceOf(DecoratorSpecInterface::class, $spec->getDecorators()[0]);
        $this->assertInstanceOf(DecoratorSpecInterface::class, $spec->getDecorators()[1]);
    }

    public function testBuildSpecWithMultipleDecoratorsMultiLine()
    {
        $this->decoratorBuilderShouldBuildOn('@first(true)', '@second(1, 2, [], {})', '@third', '@forth()');

        $spec = $this->builder->build('
        @first(true)
        @second(1, 2, [], {})
        @third @forth()
        ()');
        $this->assertInstanceOf(FunctionSpecInterface::class, $spec);
        $this->assertCount(4, $spec->getDecorators());
    }

    protected function parameterBuilderShouldBuildOn(string ...$definitions)
    {
        $map = [];

        foreach ($definitions as $definition) {
            $spec = $this->getMockForAbstractClass(ParameterSpecInterface::class);

            $map[] = [$definition, $spec];
        }

        $this->parameters_builder->method('build')
                                 ->willReturnMap($map);
    }

    protected function decoratorBuilderShouldBuildOn(string ...$definitions)
    {
        $map = [];

        foreach ($definitions as $definition) {
            $spec = $this->getMockForAbstractClass(DecoratorSpecInterface::class);

            $map[] = [$definition, $spec];
        }

        $this->decorators_builder->method('build')
                                 ->willReturnMap($map);
    }
}
