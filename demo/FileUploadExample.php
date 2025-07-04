<?php

declare(strict_types=1);

namespace Ray\InputQuery\Demo;

use Koriym\FileUpload\FileUpload;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\Attribute\InputFile;

final class FileUploadExample
{
    public function __construct(
        #[Input] public readonly string $title,
        #[InputFile] public readonly FileUpload $avatar,
        #[InputFile] public readonly ?FileUpload $banner = null,
        #[InputFile] public readonly array $documents = [],
    ) {
    }

    public function getUploadSummary(): string
    {
        $summary = "📁 File Upload Summary\n";
        $summary .= "Title: {$this->title}\n";
        $summary .= "Avatar: {$this->avatar->name} ({$this->avatar->size} bytes)\n";
        
        if ($this->banner) {
            $summary .= "Banner: {$this->banner->name} ({$this->banner->size} bytes)\n";
        } else {
            $summary .= "Banner: None\n";
        }
        
        $summary .= "Documents: " . count($this->documents) . " files\n";
        foreach ($this->documents as $i => $doc) {
            $summary .= "  - {$doc->name} ({$doc->size} bytes)\n";
        }
        
        return $summary;
    }
}