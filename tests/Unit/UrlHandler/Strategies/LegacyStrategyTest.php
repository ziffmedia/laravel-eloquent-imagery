<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\UrlHandler\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\Test\Unit\AbstractTestCase;
use ZiffMedia\LaravelEloquentImagery\UrlHandler\Strategies\LegacyStrategy;

class LegacyStrategyTest extends AbstractTestCase
{
    public function tearDown(): void
    {


        parent::tearDown();
    }

    /**
     * @dataProvider dataForGetDataFromRequest
     */
    public function testGetDataFromRequest(Request $request, $expectedCollectionArray)
    {
        $legacyStrategy = new LegacyStrategy;
        $collection = $legacyStrategy->getDataFromRequest($request);

        $this->assertEquals($expectedCollectionArray, $collection->toArray());
    }

    public function dataForGetDataFromRequest()
    {
        return [
            [$this->createMockRequestWithPath('foo.gif'), ['path' => 'foo.gif']],
            [$this->createMockRequestWithPath('foo/bar.jpg'), ['path' => 'foo/bar.jpg']],
            [$this->createMockRequestWithPath('foo/bar.v123456.jpg'), ['path' => 'foo/bar.jpg']], // version is excluded
            [$this->createMockRequestWithPath('foo/bar.fit_limit.size_x200.jpg'), ['path' => 'foo/bar.jpg', 'fit' => 'limit', 'height' => '200', 'width' => '']],
            [$this->createMockRequestWithPath('foo/bar.grayscale.jpg'), ['path' => 'foo/bar.jpg', 'grayscale' => true]],
        ];
    }

    protected function createMockRequestWithPath($path)
    {
        return Mockery::mock(Request::class)
            ->makePartial()
            ->shouldReceive('route')
            ->with('path')
            ->andReturn($path)
            ->getMock();
    }

    /**
     * @dataProvider dataForToUrl
     */
    public function testToUrl(Collection $transformations, string $expectedUrl)
    {
        $image = new Image('{id}.{extension}', []);
        $image->setStateFromAttributeData([
            'path' => 'test/1.gif',
            'extension' => 'gif',
            'width' => '50',
            'height' => '50',
            'hash' => '12345',
            'timestamp' => 1234567890,
            'metadata' => []
        ]);

        $legacyStrategy = new LegacyStrategy;
        $url = $legacyStrategy->toUrl($image, $transformations);

        $this->assertStringEndsWith($expectedUrl, $url);
    }

    public function dataForToUrl()
    {
        return [
            [collect([]), 'test/1.gif']
        ];
    }
}

