# ChunkedFileReader
Class ChunkedFileReader helps you to read content from text files without loading whole file contents in RAM. It implements a [SeekableIterator ](http://php.net/manual/ru/class.seekableiterator.php) and allows you to read file chunk by chunk.

### Warning!
If you have 32-bit installation of PHP, this class won't work with files large than 2 gigabytes. Use 64-bit and check value of [PHP_INT_MAX](http://php.net/manual/ru/reserved.constants.php) - this is maximum in bytes that you will be able to traverse. See [fseek()](http://au1.php.net/manual/ru/function.fseek.php) for more details on this problem.

### Installation
Use Composer to install package or download and include files manually to your project.

### Usage
Make sure ChunkedFileReader is installed and available in your namespace.
You can traverse file whatever way you prefer. You should have class than can handle and process chunks.

```sh
$chunkSize = 128;
$reader = new ChunkedFileReader($path, $chunkSize);
$chunkParser = new MyCustomChunkParserThatKnowsWhatToDoWithChunks();
foreach($reader as $chunkPosition => $chunk) {
    // chunks will contain 128 bytes of data
    // in order of appearance until EOF
    $chunkParser->process($chunk)
}
```
### Development
This package needs improvement, any contribution is welcomed.

### License
MIT