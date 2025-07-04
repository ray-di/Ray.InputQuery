<?php

declare(strict_types=1);

namespace Ray\InputQuery\Fake;

use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\InputFile;

final class FileUploadController
{
    /**
     * Test single file upload
     */
    public function uploadSingle(#[InputFile] FileUpload $file): void
    {
        // Single file upload
    }

    /**
     * Test multiple file uploads  
     */
    public function uploadMultiple(#[InputFile] array $files): void
    {
        // Multiple file uploads
    }

    /**
     * Test file upload with validation options
     */
    public function uploadWithValidation(
        #[InputFile(maxSize: 1024, allowedTypes: ['image/jpeg'])] FileUpload $image
    ): void {
        // File upload with validation
    }

    /**
     * Test multiple files with validation
     */
    public function uploadMultipleWithValidation(
        #[InputFile(maxSize: 2048, allowedTypes: ['image/png', 'image/jpeg'])] array $images
    ): void {
        // Multiple files with validation
    }

    /**
     * Test nullable file upload
     */
    public function uploadNullable(#[InputFile] ?FileUpload $file): void
    {
        // Nullable file upload
    }

    /**
     * Test file upload with default value
     */
    public function uploadWithDefault(#[InputFile] ?FileUpload $file = null): void
    {
        // File upload with default value
    }

    /**
     * Test required file upload (no default, not nullable)
     */
    public function uploadRequired(#[InputFile] FileUpload $file): void
    {
        // Required file upload - should throw exception if UPLOAD_ERR_NO_FILE
    }
}