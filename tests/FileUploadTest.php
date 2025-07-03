<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\FileUpload;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\InputQuery\Fake\FileUploadArrayInput;
use Ray\InputQuery\Fake\FileUploadInput;
use Ray\InputQuery\Fake\FileUploadWithOptionsInput;
use Ray\InputQuery\Fake\OptionalFileUploadInput;

class FileUploadTest extends TestCase
{
    private InputQuery $inputQuery;
    
    protected function setUp(): void
    {
        $this->inputQuery = new InputQuery(new Injector());
    }
    
    public function testFileUploadIntegration(): void
    {
        // Create mock FileUpload
        $mockAvatar = FileUpload::create([
            'name' => 'test-avatar.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/php_upload_test',
            'error' => UPLOAD_ERR_OK,
        ]);
        
        // Pass FileUpload directly in query
        $query = [
            'name' => 'Jingu',
            'avatar' => $mockAvatar
        ];
        
        $result = $this->inputQuery->create(FileUploadInput::class, $query);
        
        $this->assertSame('Jingu', $result->name);
        $this->assertSame($mockAvatar, $result->avatar);
        $this->assertSame('test-avatar.jpg', $result->avatar->name);
        $this->assertSame('image/jpeg', $result->avatar->type);
        $this->assertSame(1024, $result->avatar->size);
    }
    
    public function testFileUploadWithValidationOptions(): void
    {
        $mockAvatar = FileUpload::create([
            'name' => 'test-image.png',
            'type' => 'image/png',
            'size' => 1500,
            'tmp_name' => '/tmp/php_upload_test2',
            'error' => UPLOAD_ERR_OK,
        ]);
        
        $query = [
            'name' => 'Horikawa',
            'avatar' => $mockAvatar
        ];
        
        $result = $this->inputQuery->create(FileUploadWithOptionsInput::class, $query);
        
        $this->assertSame('Horikawa', $result->name);
        $this->assertSame($mockAvatar, $result->avatar);
        $this->assertSame('test-image.png', $result->avatar->name);
    }
    
    public function testOptionalFileUpload(): void
    {
        $query = [
            'name' => 'Test User',
            'banner' => null
        ];
        
        $result = $this->inputQuery->create(OptionalFileUploadInput::class, $query);
        
        $this->assertSame('Test User', $result->name);
        $this->assertNull($result->banner);
    }
    
    public function testFileUploadArray(): void
    {
        $mockImage1 = FileUpload::create([
            'name' => 'image1.jpg',
            'type' => 'image/jpeg',
            'size' => 1024,
            'tmp_name' => '/tmp/php_upload_1',
            'error' => UPLOAD_ERR_OK,
        ]);
        
        $mockImage2 = FileUpload::create([
            'name' => 'image2.png',
            'type' => 'image/png',
            'size' => 2048,
            'tmp_name' => '/tmp/php_upload_2',
            'error' => UPLOAD_ERR_OK,
        ]);
        
        $query = [
            'title' => 'Gallery',
            'images' => [$mockImage1, $mockImage2]
        ];
        
        $result = $this->inputQuery->create(FileUploadArrayInput::class, $query);
        
        $this->assertSame('Gallery', $result->title);
        $this->assertCount(2, $result->images);
        $this->assertSame($mockImage1, $result->images[0]);
        $this->assertSame($mockImage2, $result->images[1]);
        $this->assertSame('image1.jpg', $result->images[0]->name);
        $this->assertSame('image2.png', $result->images[1]->name);
    }
    
}