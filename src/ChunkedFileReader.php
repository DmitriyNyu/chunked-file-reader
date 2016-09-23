<?php

namespace ChunkedFileReader;

use OutOfBoundsException;
use BigFileTools\BigFileTools;

/**
 * Class ChunkedFileReader allows chunked reads from file
 * @package ChunkedFileReader
 */
class ChunkedFileReader implements \SeekableIterator
{

    /**
     * Store resource handler for file
     *
     * @var null|resource
     */
    private $__fileHandler = null;
    /**
     * Path to file
     *
     * @var string
     */
    private $__filePath = '';
    /**
     * Current position
     *
     * @var int
     */
    private $__position = 0;
    /**
     * Chunk size
     *
     * @var int
     */
    private $__chunkSize = 1;

    /**
     * ChunkedFileReader constructor.
     *
     * @todo Implement filepath check and error handling
     * @param string $pathToFile
     * @param int $chunkSize
     */
    public function __construct($pathToFile, $chunkSize = 1)
    {
        $this->__chunkSize = $chunkSize;
        if (file_exists($pathToFile)) {
            $this->__fileHandler = fopen($pathToFile, 'r');
            $this->__filePath = $pathToFile;
        }
    }

    /**
     * Return chunk at current position
     *
     * @return string
     */
    public function current()
    {
        $this->seek($this->__position);
        return fread($this->__fileHandler, $this->__chunkSize);
    }

    /**
     * Move position to next position (position + 1)
     */
    public function next()
    {
        $this->__position++;
    }

    /**
     * Return current position index
     *
     * @return int
     */
    public function key()
    {
        return $this->__position;
    }

    /**
     * Return result of check - is position inside valid bounds (0 <= position >= file length
     *
     * @return bool
     */
    public function valid()
    {
        $sizeObject = BigFileTools::createDefault()->getFile($this->__filePath);
        $bytes = $sizeObject->getSize()->minus(1);
        return (bool) $bytes->dividedBy($this->__chunkSize, 4)->isGreaterThanOrEqualTo($this->__position);
    }

    /**
     * Reset position to zero position
     */
    public function rewind()
    {
        $this->__position = 0;
        rewind($this->__fileHandler);
    }

    /**
     * Set position to given value
     *
     * @param int $position
     */
    public function seek($position)
    {
        $this->__position = (int) $position;
        if ($this->valid() === false) {
            throw new OutOfBoundsException("Position not valid: $position");
        }
        fseek($this->__fileHandler, $this->__position * $this->__chunkSize);
    }
}
