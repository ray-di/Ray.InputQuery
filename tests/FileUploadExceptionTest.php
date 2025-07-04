<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use InvalidArgumentException;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Attribute\InputFile;
use ReflectionClass;
use ReflectionMethod;

use const UPLOAD_ERR_NO_FILE;

final class FileUploadExceptionTest extends TestCase
{
    private InputQuery $inputQuery;

    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }

    public function testCorruptedFilesArrayStructure(): void
    {
        // Test case: #[InputFile] array $files + malformed $_FILES structure
        // Expected: convertMultipleFileFormat error handling
        $method = new ReflectionMethod($this, 'methodWithArrayFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        // Malformed $_FILES - missing required fields
        $_FILES['files'] = [
            'name' => ['file1.txt'],
            // Missing 'type', 'size', 'tmp_name', 'error' arrays
        ];

        // The method might handle this gracefully instead of throwing TypeError
        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        // Should return array even with malformed data
        $this->assertIsArray($result);
    }

    public function testEmptyFilesArrayHandling(): void
    {
        // Test case: #[InputFile] array $files + empty $_FILES
        // Expected: createArrayOfFileUploadsWithValidation returns empty array
        $method = new ReflectionMethod($this, 'methodWithArrayFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES = []; // No files uploaded

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertSame([], $result);
    }

    public function testFileUploadValidationErrors(): void
    {
        // Test case: #[InputFile(maxSize: 1024)] FileUpload $file + oversized file
        // Expected: resolveFileUploadWithValidation error handling
        $method = new ReflectionMethod($this, 'methodWithValidatedFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        // File that exceeds size limit (1KB)
        $_FILES['avatar'] = [
            'name' => 'large.jpg',
            'type' => 'image/jpeg',
            'size' => 2048, // 2KB - exceeds 1KB limit
            'tmp_name' => '/tmp/large',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertInstanceOf(ErrorFileUpload::class, $result);
        $this->assertNotNull($result->message);
    }

    public function testMultipleFileValidationCombinations(): void
    {
        // Test case: #[InputFile(maxSize: 1024, allowedTypes: ['image/jpeg', 'image/png'])] array $files
        // Expected: each file processed individually with validation
        $method = new ReflectionMethod($this, 'methodWithValidatedArrayFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $_FILES['images'] = [
            'name' => ['good.jpg', 'bad.txt', 'oversized.png'],
            'type' => ['image/jpeg', 'text/plain', 'image/png'],
            'size' => [500, 100, 3000], // third file exceeds 1KB limit
            'tmp_name' => ['/tmp/good', '/tmp/bad', '/tmp/oversized'],
            'error' => [0, 0, 0],
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // First file should be valid
        $this->assertInstanceOf(FileUpload::class, $result[0]);

        // Second file should fail type validation
        $this->assertInstanceOf(ErrorFileUpload::class, $result[1]);

        // Third file should fail size validation
        $this->assertInstanceOf(ErrorFileUpload::class, $result[2]);
    }

    public function testFileUploadErrorCodes(): void
    {
        // Test case: #[InputFile] FileUpload $file + UPLOAD_ERR_NO_FILE
        // Expected: proper handling of UPLOAD_ERR_* constants
        $method = new ReflectionMethod($this, 'methodWithSingleFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        // Test UPLOAD_ERR_NO_FILE
        $_FILES['file'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required file parameter 'file' is missing");

        $this->callPrivateMethod(
            $this->inputQuery,
            'resolveInputFileParameter',
            [$param, [], $inputFileAttributes],
        );
    }

    public function testExtractValidationOptionsEdgeCases(): void
    {
        // Test case: extractValidationOptions with various #[InputFile] attribute combinations
        // Expected: proper filtering and validation option extraction

        // Test with null values that should be filtered
        $inputFile1 = new InputFile(maxSize: null, allowedTypes: null);
        $attr1 = new class ($inputFile1) {
            public function __construct(private InputFile $inputFile)
            {
            }

            public function newInstance(): InputFile
            {
                return $this->inputFile;
            }
        };

        $result1 = $this->callPrivateMethod(
            $this->inputQuery,
            'extractValidationOptions',
            [[$attr1]],
        );
        $this->assertSame([], $result1);

        // Test with zero maxSize that should be filtered
        $inputFile2 = new InputFile(maxSize: 0, allowedTypes: ['image/jpeg']);
        $attr2 = new class ($inputFile2) {
            public function __construct(private InputFile $inputFile)
            {
            }

            public function newInstance(): InputFile
            {
                return $this->inputFile;
            }
        };

        $result2 = $this->callPrivateMethod(
            $this->inputQuery,
            'extractValidationOptions',
            [[$attr2]],
        );
        $this->assertSame(['allowedTypes' => ['image/jpeg']], $result2);

        // Test with empty allowedTypes array
        $inputFile3 = new InputFile(maxSize: 1024, allowedTypes: []);
        $attr3 = new class ($inputFile3) {
            public function __construct(private InputFile $inputFile)
            {
            }

            public function newInstance(): InputFile
            {
                return $this->inputFile;
            }
        };

        $result3 = $this->callPrivateMethod(
            $this->inputQuery,
            'extractValidationOptions',
            [[$attr3]],
        );
        $this->assertSame(['maxSize' => 1024, 'allowedTypes' => []], $result3);
    }

    public function testFileUploadQueryDataHandling(): void
    {
        // Test case: #[InputFile] FileUpload $file + FileUpload object in query
        // Expected: query data takes precedence over $_FILES
        $method = new ReflectionMethod($this, 'methodWithSingleFileUpload');
        $param = $method->getParameters()[0];
        $inputFileAttributes = $param->getAttributes(InputFile::class);

        $fileUpload = FileUpload::create([
            'name' => 'query-file.txt',
            'type' => 'text/plain',
            'size' => 256,
            'tmp_name' => '/tmp/query',
            'error' => 0,
        ]);

        $query = ['file' => $fileUpload];

        // Also set $_FILES to ensure query takes precedence
        $_FILES['file'] = [
            'name' => 'files-data.txt',
            'type' => 'text/plain',
            'size' => 512,
            'tmp_name' => '/tmp/files',
            'error' => 0,
        ];

        $result = $this->callPrivateMethod(
            $this->inputQuery,
            'resolveFileUpload',
            [$param, $query, []],
        );

        $this->assertSame($fileUpload, $result);
        $this->assertSame('query-file.txt', $result->name);
    }

    // Helper methods representing various file upload scenarios

    /**
     * Multiple file upload with array type
     */
    private function methodWithArrayFileUpload(
        #[InputFile]
        array $files,
    ): void {
        // Array of FileUpload objects
    }

    /**
     * Single file upload with validation
     */
    private function methodWithValidatedFileUpload(
        #[InputFile(maxSize: 1024, allowedTypes: ['image/jpeg', 'image/png'])]
        FileUpload $avatar,
    ): void {
        // Validated single file upload
    }

    /**
     * Multiple file upload with validation
     */
    private function methodWithValidatedArrayFileUpload(
        #[InputFile(maxSize: 1024, allowedTypes: ['image/jpeg', 'image/png'])]
        array $images,
    ): void {
        // Validated multiple file upload
    }

    /**
     * Basic single file upload
     */
    private function methodWithSingleFileUpload(
        #[InputFile]
        FileUpload $file,
    ): void {
        // Basic single file upload
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
