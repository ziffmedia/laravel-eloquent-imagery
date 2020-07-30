<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\Eloquent;

use ArrayIterator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ZiffMedia\LaravelEloquentImagery\Eloquent\Image;
use ZiffMedia\LaravelEloquentImagery\Eloquent\ImageCollection;
use ZiffMedia\LaravelEloquentImagery\EloquentImageryProvider;
use ZiffMedia\LaravelEloquentImagery\Test\Unit\AbstractTestCase;

class ImageCollectionTest extends AbstractTestCase
{
    protected function getPackageProviders($app)
    {
        return [EloquentImageryProvider::class];
    }

    public function testSetStateFromDataAttribute()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));

        $state = [
            'autoincrement' => 10,
            'images'        => [
                [
                    'path'      => 'foo/bar.jpg',
                    'extension' => 'jpg',
                    'width'     => 1,
                    'height'    => 1,
                    'hash'      => '1234567890',
                    'timestamp' => 12345,
                    'metadata'  => []
                ]
            ],
            'metadata'      => [
                'foo' => 'bar'
            ]
        ];

        $imageCollection->setStateFromAttributeData($state);

        $this->assertCount(1, $imageCollection);
        $image = $imageCollection[0];

        $this->assertEquals('foo/bar.jpg', $image->path);
        $this->assertTrue($image->exists());
        $this->assertEquals('http://localhost/imagery/foo/bar.jpg', $image->url());
        $this->assertEquals('bar', $imageCollection->metadata()['foo']);
    }

    public function testSetStateFromDataAttributeClearsPreviousState()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));

        $state = [
            'images'   => [
                [
                    'path'      => 'foo/bar.jpg',
                    'extension' => 'jpg',
                    'width'     => 1,
                    'height'    => 1,
                    'hash'      => '1234567890',
                    'timestamp' => 12345,
                    'metadata'  => []
                ]
            ],
            'metadata' => [
                'foo' => 'bar'
            ]
        ];

        $imageCollection->setStateFromAttributeData($state);

        // call set state again to ensure that the items and metdata collection are not appending data
        $imageCollection->setStateFromAttributeData($state);

        $this->assertCount(1, $imageCollection);
    }

    public function testPathHasReplacements()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));

        $this->assertTrue($imageCollection->pathHasReplacements());

        $imageCollection->updatePath(['name' => 'bar'], new TestAssets\FooModel);

        $this->assertFalse($imageCollection->pathHasReplacements());
    }

    public function testOffsetExists()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));

        $this->assertTrue(isset($imageCollection[0]));
        $this->assertFalse(isset($imageCollection[1]));
    }

    public function testOffsetSet()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));

        // add image
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));

        // add bytes that will go through createImage()
        $imageCollection[] = file_get_contents(__DIR__ . '/TestAssets/30.png');

        $this->assertCount(2, $imageCollection);
        $this->assertInstanceOf(Image::class, $imageCollection[0]);
        $this->assertInstanceOf(Image::class, $imageCollection[1]);
    }

    public function testOffsetUnset()
    {
        $imageCollection = new ImageCollection(new Image('foo/{name}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.png'));

        $this->assertCount(2, $imageCollection);

        unset($imageCollection[1]);

        $this->assertCount(1, $imageCollection);
    }


    public function testFlush()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.png'));

        $model = new TestAssets\FooModel();
        $model->setRawAttributes(['slug' => 'bar-baz'], true);

        $imageCollection->updatePath([], $model);
        $imageCollection->flush();

        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar-baz-1.jpg');
        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar-baz-2.png');

        $state = $imageCollection->getStateAsAttributeData();

        // reset collection

        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection->setStateFromAttributeData($state);

        unset($imageCollection[1]);
        $imageCollection->flush();

        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar-baz-1.jpg');
        $this->assertFileDoesNotExist(__DIR__ . '/../../storage/foo/bar-baz-2.png');
    }

    public function testGetStateAsDataAttribute()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.png'));

        $model = new TestAssets\FooModel();
        $model->setRawAttributes(['slug' => 'boom'], true);

        $imageCollection->updatePath([], $model);

        $expected = [
            'autoincrement' => 3,
            'images'        => [
                [
                    'path'      => 'foo/boom-1.jpg',
                    'extension' => 'jpg',
                    'width'     => 30,
                    'height'    => 30,
                    'hash'      => '809dcbbcd89eb8a275a6c6f4556e1f41',
                    'timestamp' => Carbon::getTestNow()->unix(),
                    'metadata'  => [],
                    'index'     => 1,
                ],
                [
                    'path'      => 'foo/boom-2.png',
                    'extension' => 'png',
                    'width'     => 30,
                    'height'    => 30,
                    'hash'      => '7692f4f945481216e41ce0a8f42f6ed6',
                    'timestamp' => Carbon::getTestNow()->unix(),
                    'metadata'  => [],
                    'index'     => 2,
                ]
            ],
            'metadata'      => []
        ];

        $this->assertEquals($expected, $imageCollection->getStateAsAttributeData());
        $this->assertEquals($expected, $imageCollection->toArray());
    }

    public function testRemove()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.png'));
        $imageCollection->updatePath(['slug' => 'bar-baz'], new TestAssets\FooModel);
        $imageCollection->flush();

        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar-baz-1.jpg');
        $this->assertFileExists(__DIR__ . '/../../storage/foo/bar-baz-2.png');

        $state = $imageCollection->getStateAsAttributeData();

        // reset collection

        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection->setStateFromAttributeData($state);
        $imageCollection->remove();
        $imageCollection->flush();

        $this->assertFileDoesNotExist(__DIR__ . '/../../storage/foo/bar-baz-1.jpg');
        $this->assertFileDoesNotExist(__DIR__ . '/../../storage/foo/bar-baz-2.png');
    }

    public function testPurgeRemovedImages()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.jpg'));
        $imageCollection[] = $imageCollection->createImage(file_get_contents(__DIR__ . '/TestAssets/30.png'));

        $this->assertCount(2, $imageCollection);

        // remove image directly
        $imageCollection[0]->remove();

        $this->assertCount(2, $imageCollection);

        // purge will removed these image->remove() images
        $imageCollection->purgeRemovedImages();

        $this->assertCount(1, $imageCollection);
    }

    public function testGetWrappedCollectionForImages()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $this->assertInstanceOf(Collection::class, $imageCollection->getWrappedCollectionForImages());
    }

    public function testGetIterator()
    {
        $imageCollection = new ImageCollection(new Image('foo/{slug}-{index}.{extension}', []));
        $this->assertInstanceOf(ArrayIterator::class, $imageCollection->getIterator());
    }
}

