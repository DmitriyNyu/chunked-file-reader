<?php

use ChunkedFileReader\ChunkedFileReader;
use DummyFileGenerator\DummyFileGenerator;

class ChunkedFileReaderTest extends PHPUnit_Framework_TestCase
{
    private $__testFileDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
    private $__testFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'testFile.txt';

    /**
     * Setting up test files.
     *
     * @todo Refactor to use virtual file system
     * @throws Exception
     */
    public function setUp()
    {
        if (!is_dir($this->__testFileDir)) {
            if (!mkdir($this->__testFileDir)) {
                throw new Exception('Cannot create directory for test files');
            }
        }
        if (!is_writable($this->__testFileDir)) {
            throw new Exception('Directory is not writeable');
        }
        $fileGenerator = new DummyFileGenerator();
        $fileGenerator->generateFile($this->__testFilePath, '12345678901234567890', 20);
    }

    /**
     * Cleaning up after test
     */
    public function tearDown()
    {
        if (file_exists($this->__testFilePath)) {
            unlink($this->__testFilePath);
        }
        if (is_dir($this->__testFileDir)) {
            rmdir($this->__testFileDir);
        }
    }

    /**
     * Simple check that files are loaded and namespaces are correct
     */
    public function testFileReaderCanBeInstantiated()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $this->assertInstanceOf('ChunkedFileReader\ChunkedFileReader', $fileReader, 'ChunkedFileReader must be an instance of ChunkedFileReader');
    }

    /**
     * Testing setting position
     */
    public function testFileReaderSeekChangesPositionToCustomValueInBounds()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $fileReader->seek(9);
        $this->assertEquals(9, $fileReader->key());
    }

    /**
     * Testing exception, when seeking outside of bounds
     */
    public function testFileReaderThrowsAnExceptionWhenSeekingOutOfBounds()
    {
        $this->expectException(OutOfBoundsException);
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $fileReader->seek(100);
    }

    /**
     * Testing valid method
     */
    public function testFileReaderShoudValidateBoundsOfFile()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $this->assertTrue($fileReader->valid());
        $fileReader->seek(19);
        $fileReader->next();
        $this->assertFalse($fileReader->valid());
    }

    /**
     * Testing returns value from current positions
     */
    public function testFileReaderShouldReturnCharactersInCurrentPosition()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $this->assertEquals('1', $fileReader->current());
        $fileReader->seek(1);
        $this->assertEquals('2', $fileReader->current());
        $fileReader->seek(9);
        $this->assertEquals('0', $fileReader->current());
    }

    /**
     *  Ensuring that position does not change after current() call
     */
    public function testFileReaderShouldReturnSameCharactersWhenPositionIsNotChanged()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $fileReader->seek(9);
        $this->assertEquals('0', $fileReader->current());
        // second time
        $this->assertEquals('0', $fileReader->current());
    }

    /**
     * Testing of key() method, should return current position
     */
    public function testFileReaderKeyMethodShouldReturnCurrentPosition()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $this->assertEquals(0, $fileReader->key());
        $fileReader->seek(9);
        $this->assertEquals(9, $fileReader->key());
    }

    /**
     * Testing rewind() method
     */
    public function testFileReaderRewindMethodShouldResetPositionPointerToZero()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $fileReader->seek(9);
        $this->assertEquals(9, $fileReader->key());
        $fileReader->rewind();
        $this->assertEquals(0, $fileReader->key());
    }

    /**
     * Making sure next() increased position
     */
    public function testFileReaderNextMethodShouldIncreasePositionPointerByOne()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath);
        $fileReader->seek(9);
        $this->assertEquals(9, $fileReader->key());
        $fileReader->next();
        $this->assertEquals(10, $fileReader->key());
        $fileReader->next();
        $this->assertEquals(11, $fileReader->key());
    }

    /**
     * Making sure chunks works and returns correct value
     */
    public function testFileReaderShouldSupportChunkedReads()
    {
        $fileReader = new ChunkedFileReader($this->__testFilePath, 3);
        $this->assertEquals('123', $fileReader->current());
        $fileReader->next();
        $this->assertEquals('456', $fileReader->current());
        $fileReader->seek(6);
        $this->assertEquals('90', $fileReader->current());
    }

    /**
     * Making sure valid() works with chunks
     */
    public function testFileReaderShouldUseChunkSizeToCalculateBounds()
    {
        $this->expectException('OutOfBoundsException');
        $fileReader = new ChunkedFileReader($this->__testFilePath, 3);
        $fileReader->seek(7);
    }

    /**
     * Making sure we read correct data from very large file
     */
    public function testFileReaderShouldWorkWithVeryLargeFiles()
    {
        $fileGenerator = new DummyFileGenerator();
        $fileGenerator->generateFile($this->__testFilePath, '12', 2147483648);
        $fileReader = new ChunkedFileReader($this->__testFilePath, 1);

        $fixedPositions = [0, 1, 2, 3, 2047483648, 2047483649, 2047483650];

        foreach ($fixedPositions as $fixedPosition) {
            $value = 2;
            if ($this->__isNumberEven($fixedPosition)) {
                $value = 1;
            }
            $fileReader->seek($fixedPosition);
            $this->assertEquals($fixedPosition, $fileReader->key());
            $this->assertEquals($value, $fileReader->current(), "Value expected to be $value for position $fixedPosition");
        }

        $randomPositions = [];
        for ($i = 0; $i < 1000; $i++) {
            $randomPositions[] = mt_rand(1073741824, 2147483647);
        }

        foreach ($randomPositions as $randomPosition) {
            $value = 2;
            if ($this->__isNumberEven($randomPosition)) {
                $value = 1;
            }
            $fileReader->seek($randomPosition);
            $this->assertEquals($value, $fileReader->current(), "Value expected to be $value for position $randomPosition");
            $this->assertEquals($randomPosition, $fileReader->key());
        }
    }

    /**
     * Helper method that detects if number is even
     *
     * @param $num
     * @return bool
     */
    private function __isNumberEven($num)
    {
        return (bool) (((int) $num % 2) === 0);
    }
}
