<?php

declare(strict_types=1);

namespace Ray\InputQuery;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use ReflectionNamedType;
use ReflectionUnionType;

use function is_subclass_of;

/**
 * Utility class for FileUpload type checking
 */
final class FileUploadTypeChecker
{
    /**
     * Check if a class name represents a FileUpload type
     */
    public static function isFileUploadType(string $className): bool
    {
        if ($className === FileUpload::class || $className === ErrorFileUpload::class) {
            return true;
        }

        return is_subclass_of($className, FileUpload::class) || is_subclass_of($className, ErrorFileUpload::class);
    }

    /**
     * Check if union type is valid for file uploads
     */
    public static function isValidFileUploadUnion(ReflectionUnionType $type): bool
    {
        $unionTypes = $type->getTypes();

        foreach ($unionTypes as $unionType) {
            if (! $unionType instanceof ReflectionNamedType) {
                // @codeCoverageIgnoreStart
                // This case occurs with PHP 8.2+ intersection types in union types like (A&B)|C
                // Cannot be tested in PHP < 8.2 due to syntax errors
                return false;
                // @codeCoverageIgnoreEnd
            }

            $typeName = $unionType->getName();
            // Allow FileUpload types (with or without namespace) and null
            if (! self::isFileUploadType($typeName) && $typeName !== 'null') {
                return false;
            }
        }

        return true;
    }
}
