<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;

final class InputFileParameterTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testResolveInputFileParameterWithSingleFileUpload(): void
    {
        // Test the "Handle single FileUpload type" path (lines 137-141)
        $method = new ReflectionMethod($this, 'methodWithSingleFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['avatar'] = [
            'name' => 'avatar.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/avatar',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('avatar.jpg', $result->name);
    }

    public function testResolveInputFileParameterWithArrayOfFileUploads(): void
    {
        // Test the "Handle array of FileUpload" path (lines 143-148)
        $method = new ReflectionMethod($this, 'methodWithArrayOfFileUploads');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['images'] = [
            'name' => ['image1.jpg', 'image2.png'],
            'type' => ['image/jpeg', 'image/png'],
            'size' => [1024, 2048],
            'tmp_name' => ['/tmp/image1', '/tmp/image2'],
            'error' => [0, 0],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(FileUpload::class, $result[0]);
        $this->assertInstanceOf(FileUpload::class, $result[1]);
        $this->assertSame('image1.jpg', $result[0]->name);
        $this->assertSame('image2.png', $result[1]->name);
    }

    public function testResolveInputFileParameterWithUnionType(): void
    {
        // Test the union type path (lines 132-135)
        $method = new ReflectionMethod($this, 'methodWithFileUploadUnion');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['file'] = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('test.jpg', $result->name);
    }

    public function testResolveInputFileParameterFallbackPath(): void
    {
        // Test the fallback path (line 152) with non-FileUpload, non-array type
        $method = new ReflectionMethod($this, 'methodWithStringInputFile');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['filename'] = [
            'name' => 'document.txt',
            'type' => 'text/plain',
            'size' => 512,
            'tmp_name' => '/tmp/document',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        // Even though parameter is string, it should still create FileUpload due to #[InputFile]
        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('document.txt', $result->name);
    }

    // Test methods representing real use cases

    /**
     * Real use case: Single profile image upload
     */
    private function methodWithSingleFileUpload(
        #[InputFile(maxSize: 1024 * 1024, allowedTypes: ['image/jpeg', 'image/png'])]
        FileUpload $avatar,
    ): void {
        // Upload profile avatar
    }

    /**
     * Real use case: Multiple image gallery upload
     */
    private function methodWithArrayOfFileUploads(
        #[InputFile(maxSize: 5 * 1024 * 1024, allowedTypes: ['image/jpeg', 'image/png'])]
        array $images,  // FileUpload[]
    ): void {
        // Upload multiple gallery images
    }

    /**
     * Real use case: File upload that might fail validation
     */
    private function methodWithFileUploadUnion(
        #[InputFile(maxSize: 2 * 1024 * 1024)]
        FileUpload|ErrorFileUpload $file,
    ): void {
        // Handle file upload with possible validation errors
    }

    /**
     * Edge case: String parameter with InputFile attribute
     */
    private function methodWithStringInputFile(
        #[InputFile]
        string $filename,
    ): void {
        // Unusual but possible: string param with file attribute
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
