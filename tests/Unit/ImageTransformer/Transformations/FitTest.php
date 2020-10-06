<?php

namespace ZiffMedia\LaravelEloquentImagery\Test\Unit\ImageTransformer\Transformations;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZiffMedia\LaravelEloquentImagery\ImageTransformer\Transformations\Fit;

class FitTest extends TestCase
{
    public function testFitLimitWithImageThatIsLargerThanBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(\Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(500);
        $imagickMock->method('getImageHeight')->willReturn(500);

        $this->createMockIterator($imagickMock, [$imagickMock], 2);

        $imagickMock->expects($this->once())
            ->method('scaleImage')
            ->with($this->equalTo(100), $this->equalTo(100), $this->equalTo(true));

        $fit->applyImagick($arguments, $imagickMock);
    }

    public function testFitLimitWithImageThatIsSmallerThanBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(\Imagick::class);
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

        $imagickMock = $this->createMock(\Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(200);
        $imagickMock->method('getImageHeight')->willReturn(50);

        $this->createMockIterator($imagickMock, [$imagickMock], 2);

        $imagickMock->expects($this->once())
            ->method('scaleImage')
            ->with($this->equalTo(100), $this->equalTo(100), $this->equalTo(true));

        $fit->applyImagick($arguments, $imagickMock);
    }

    public function testFitLimitWithImageThatHasHeightOutsideBox()
    {
        $fit = new Fit();

        $arguments = collect(['fit' => 'limit', 'width' => 100, 'height' => 100]);

        $imagickMock = $this->createMock(\Imagick::class);
        $imagickMock->method('getImageWidth')->willReturn(50);
        $imagickMock->method('getImageHeight')->willReturn(500);

        $this->createMockIterator($imagickMock, [$imagickMock], 2);

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
    protected function createMockIterator(\Iterator $iterator, array $items, $startSequenceAt = 0, $includeCallsToKey = false)
    {
        $iterator->expects($this->at($startSequenceAt))->method('rewind');

        foreach ($items as $k => $v) {
            $iterator->expects($this->at(++$startSequenceAt))->method('valid')->will($this->returnValue(true));
            $iterator->expects($this->at(++$startSequenceAt))->method('current')->will($this->returnValue($v));

            if ($includeCallsToKey) {
                $iterator->expects($this->at(++$startSequenceAt))->method('key')->will($this->returnValue($k));
            }

            $iterator->expects($this->at(++$startSequenceAt))->method('next');
        }

        $iterator->expects($this->at($startSequenceAt))->method('valid')->will($this->returnValue(false));
    }
}

