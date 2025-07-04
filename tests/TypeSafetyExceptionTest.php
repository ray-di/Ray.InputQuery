<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use DateTime;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

final class TypeSafetyExceptionTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testInvalidTypeWithInputFileAttribute(): void
    {
        // Test case: #[InputFile] int $invalidType
        // Expected: フォールバック処理パス (resolveFileUploadWithValidation)
        $method = new ReflectionMethod($this, 'methodWithIntInputFile');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['count'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        // Even with int type, should create FileUpload due to #[InputFile]
        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testMixedTypeWithInputFileAttribute(): void
    {
        // Test case: #[InputFile] mixed $data
        // Expected: フォールバック処理パス (line 152)
        $method = new ReflectionMethod($this, 'methodWithMixedInputFile');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['data'] = [
            'name' => 'mixed.txt',
            'type' => 'text/plain',
            'size' => 512,
            'tmp_name' => '/tmp/mixed',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('mixed.txt', $result->name);
    }

    public function testObjectTypeWithInputFileAttribute(): void
    {
        // Test case: #[InputFile] \stdClass $object
        // Expected: フォールバック処理パス (非FileUpload、非array型)
        $method = new ReflectionMethod($this, 'methodWithObjectInputFile');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['object'] = [
            'name' => 'object.txt',
            'type' => 'text/plain',
            'size' => 256,
            'tmp_name' => '/tmp/object',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('object.txt', $result->name);
    }

    public function testResolveInputFileParameterWithNoType(): void
    {
        // Test case: パラメータにタイプヒントがない場合
        // Expected: 型がnullの場合の処理
        $method = new ReflectionMethod($this, 'methodWithNoTypeHint');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['noType'] = [
            'name' => 'notype.txt',
            'type' => 'text/plain',
            'size' => 128,
            'tmp_name' => '/tmp/notype',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        // Should still create FileUpload due to #[InputFile] attribute
        $this->assertInstanceOf(FileUpload::class, $result);
    }

    public function testComplexUnionTypeWithInputFile(): void
    {
        // Test case: #[InputFile] FileUpload|string|null $complex
        // Expected: ReflectionUnionType処理パス
        $method = new ReflectionMethod($this, 'methodWithComplexUnion');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['complex'] = [
            'name' => 'complex.txt',
            'type' => 'text/plain',
            'size' => 2048,
            'tmp_name' => '/tmp/complex',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(FileUpload::class, $result);
        $this->assertSame('complex.txt', $result->name);
    }

    public function testFileUploadTypeDetectionEdgeCase(): void
    {
        // Test case: isFileUploadType() の境界値テスト
        // Expected: 様々なクラス名での型判定

        // Test FileUpload
        $result1 = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            [FileUpload::class],
        );
        $this->assertTrue($result1);

        // Test ErrorFileUpload
        $result2 = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            [ErrorFileUpload::class],
        );
        $this->assertTrue($result2);

        // Test non-existent class
        $result3 = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            ['NonExistentFileUploadClass'],
        );
        $this->assertFalse($result3);

        // Test completely unrelated class
        $result4 = $this->callPrivateMethod(
            $this->inputQuery,
            'isFileUploadType',
            [DateTime::class],
        );
        $this->assertFalse($result4);
    }

    // Helper methods representing edge cases

    /**
     * Edge case: int type with InputFile attribute
     */
    private function methodWithIntInputFile(
        #[InputFile]
        int $count,
    ): void {
        // Unusual but possible case
    }

    /**
     * Edge case: mixed type with InputFile attribute
     */
    private function methodWithMixedInputFile(
        #[InputFile]
        mixed $data,
    ): void {
        // Generic data with file attribute
    }

    /**
     * Edge case: object type with InputFile attribute
     */
    private function methodWithObjectInputFile(
        #[InputFile]
        stdClass $object,
    ): void {
        // Object type with file attribute
    }

    /**
     * Edge case: no type hint with InputFile attribute
     */
    private function methodWithNoTypeHint(
        #[InputFile]
        $noType,
    ): void {
        // No type declaration
    }

    /**
     * Edge case: complex union type with InputFile
     */
    private function methodWithComplexUnion(
        #[InputFile]
        FileUpload|string|null $complex,
    ): void {
        // Complex union type
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
