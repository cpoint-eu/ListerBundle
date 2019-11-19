<?php
namespace Povs\ListerBundle\Type\QueryType;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Povilas Margaiatis <p.margaitis@gmail.com>
 */
class BetweenQueryTypeTest extends TestCase
{
    /**
     * @dataProvider filterProvider
     * @param $val1
     * @param $val2
     * @param $value
     * @param $delimiter
     */
    public function testFilterWithValueAsString($val1, $val2, $value, $delimiter): void
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('andWhere')
            ->with('foo BETWEEN :bar_from AND :bar_to')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                [':bar_from', $val1],
                [':bar_to', $val2]
            )->willReturnSelf();

        $type = $this->getType(['foo'], 'foo', ['value_delimiter' => $delimiter]);
        $type->filter($queryBuilderMock, 'bar', $value);
    }

    /**
     * @return array
     */
    public function filterProvider(): array
    {
        return [
            ['val1', 'val2', 'val1-val2', '-'],
            ['val1-val2', null, 'val1-val2', '||'],
            ['val1', 'val2', ['val1', 'val2'], '-'],
            ['val1', null, ['val1'], '-']
        ];
    }

    public function testConfigureOptions(): void
    {
        $optionResolver = new OptionsResolver();
        $type = $this->getType([], '', []);
        $type->configureOptions($optionResolver);

        $this->assertEquals(['value_delimiter' => 'foo'], $optionResolver->resolve(['value_delimiter' => 'foo']));
    }

    public function testConfigureOptionsDefault(): void
    {
        $optionResolver = new OptionsResolver();
        $type = $this->getType([], '', []);
        $type->configureOptions($optionResolver);

        $this->assertEquals(['value_delimiter' => '-'], $optionResolver->resolve());
    }

    /**
     * @param array  $paths
     * @param string $path
     * @param array  $options
     *
     * @return BetweenQueryType
     */
    private function getType(array $paths, string $path, array $options): BetweenQueryType
    {
        $type = new BetweenQueryType();
        $type->setPaths($paths);
        $type->setPath($path);
        $type->setOptions($options);

        return $type;
    }
}