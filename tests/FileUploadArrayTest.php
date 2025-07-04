<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use ReflectionClass;

use const UPLOAD_ERR_NO_FILE;

final class FileUploadArrayTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testCreateArrayOfFileUploadsWithValidation(): void
    {
        // Test creating array of file uploads with validation
        $_FILES['images'] = [
            'name' => ['image1.jpg', 'image2.png'],
            'type' => ['image/jpeg', 'image/png'],
            'size' => [1024, 2048],
            'tmp_name' => ['/tmp/test1', '/tmp/test2'],
            'error' => [0, 0],
        ];

        $query = [];
        $inputFileAttributes = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploadsWithValidation',
            ['images', $query, $inputFileAttributes],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
        $this->assertInstanceOf(FileUpload::class, $result[1]);
    }

    public function testCreateArrayOfFileUploadsDirectly(): void
    {
        // Test creating array of file uploads without validation
        $_FILES['images'] = [
            'name' => ['image1.jpg'],
            'type' => ['image/jpeg'],
            'size' => [1024],
            'tmp_name' => ['/tmp/test1'],
            'error' => [0],
        ];

        $query = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['images', $query, []],
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
    }

    public function testCreateArrayOfFileUploadsWithMissingFiles(): void
    {
        // Test when no files are in $_FILES
        $_FILES = [];
        $query = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['images', $query, []],
        );

        $this->assertSame([], $result);
    }

    public function testCreateArrayOfFileUploadsWithSingleFileArray(): void
    {
        // Test when file data is array of complete file arrays (not multiple format)
        $_FILES['images'] = [
            0 => [
                'name' => 'file1.jpg',
                'type' => 'image/jpeg',
                'size' => 1024,
                'tmp_name' => '/tmp/test1',
                'error' => 0,
            ],
            1 => [
                'name' => 'file2.jpg',
                'type' => 'image/jpeg',
                'size' => 2048,
                'tmp_name' => '/tmp/test2',
                'error' => 0,
            ],
        ];

        $query = [];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['images', $query, []],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
        $this->assertInstanceOf(FileUpload::class, $result[1]);
    }

    public function testCreateArrayOfFileUploadsWithQueryData(): void
    {
        // Test when file data comes from query instead of $_FILES
        $_FILES = [];
        $query = [
            'images' => [
                FileUpload::create([
                    'name' => 'test.jpg',
                    'type' => 'image/jpeg',
                    'size' => 1024,
                    'tmp_name' => '/tmp/test',
                    'error' => 0,
                ]),
            ],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'createArrayOfFileUploads',
            ['images', $query, []],
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
    }

    public function testConvertMultipleFileFormatWithValidation(): void
    {
        // Test the convertMultipleFileFormat method with validation options
        $multipleFileData = [
            'name' => ['test1.jpg', 'test2.png'],
            'type' => ['image/jpeg', 'image/png'],
            'size' => [500, 1000],
            'tmp_name' => ['/tmp/test1', '/tmp/test2'],
            'error' => [0, 0],
        ];

        $validationOptions = [
            'maxSize' => 2048,
            'allowedTypes' => ['image/jpeg', 'image/png'],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'convertMultipleFileFormat',
            [$multipleFileData, $validationOptions],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
        $this->assertInstanceOf(FileUpload::class, $result[1]);
    }

    public function testConvertMultipleFileFormatWithSkippedFiles(): void
    {
        // Test with UPLOAD_ERR_NO_FILE which should be skipped
        $multipleFileData = [
            'name' => ['', 'test.jpg'],
            'type' => ['', 'image/jpeg'],
            'size' => [0, 1024],
            'tmp_name' => ['', '/tmp/test'],
            'error' => [UPLOAD_ERR_NO_FILE, 0],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'convertMultipleFileFormat',
            [$multipleFileData, []],
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only one file should be processed
        $this->assertArrayHasKey(1, $result); // Should be at index 1
        $this->assertInstanceOf(FileUpload::class, $result[1]);
    }

    private function callPrivateMethod(object $object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    protected function tearDown(): void
    {
        $_FILES = [];
    }
}
