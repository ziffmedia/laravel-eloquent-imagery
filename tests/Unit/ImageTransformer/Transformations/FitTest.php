<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\ImageTransformer\Transformations;

use Imagick;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations\Fit;

class FitTest extends TestCase
{
    public function testFitLimitWithImageThatIsLargerThanBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(500);
        $imagickMock->method('getImageHeight')->willReturn(500);

        $this->createMockIterator($imagickMock, [$imagickMock]);

        $imagickMock->expects($this->once())
            ->method('scaleImage')
            ->with($this->equalTo(100), $this->equalTo(100), $this->equalTo(true));

        $fit->applyImagick($arguments, $imagickMock);
    }

    public function testFitLimitWithImageThatIsSmallerThanBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(50);
        $imagickMock->method('getImageHeight')->willReturn(50);

        // imagick should not be iterated or scale attempted
        $imagickMock->expects($this->never())->method('rewind');
        $imagickMock->expects($this->never())->method('scaleImage');

        $fit->applyImagick($arguments, $imagickMock);
    }

    public function testFitLimitWithImageThatHasWidthOutsideBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(200);
        $imagickMock->method('getImageHeight')->willReturn(50);

        $this->createMockIterator($imagickMock, [$imagickMock]);

        $imagickMock->expects($this->once())
            ->method('scaleImage')
            ->with($this->equalTo(100), $this->equalTo(100), $this->equalTo(true));

        $fit->applyImagick($arguments, $imagickMock);
    }

    public function testFitLimitWithImageThatHasHeightOutsideBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(50);
        $imagickMock->method('getImageHeight')->willReturn(500);

        $this->createMockIterator($imagickMock, [$imagickMock]);

        $imagickMock->expects($this->once())
            ->method('scaleImage')
            ->with($this->equalTo(100), $this->equalTo(100), $this->equalTo(true));

        $fit->applyImagick($arguments, $imagickMock);
    }

    /**
     * @param \Iterator|MockObject $iterator
     * @param array $items
     * @param int $startSequenceAt
     * @param false $includeCallsToKey
     */
    protected function createMockIterator(\Iterator $iterator, array $items)
    {
        $iteratorState = new \ArrayObject([
            'iteration' => 0,
            'values' => $items
        ]);

        $iterator->method('rewind');

        $iterator->method('valid')->will($this->returnCallback(function () use ($iteratorState) {
            return isset($iteratorState['values'][$iteratorState['iteration']]);
        }));

        $iterator->method('current')->will($this->returnCallback(function () use ($iteratorState) {
            return $iteratorState['values'][$iteratorState['iteration']];
        }));

        $iterator->method('next')->will($this->returnCallback(function () use ($iteratorState) {
            return $iteratorState['iteration']++;
        }));
    }
}

