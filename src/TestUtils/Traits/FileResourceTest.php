<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\TestUtils\Traits;

use InvalidArgumentException;
use Nette\Http\FileUpload;

trait FileResourceTest
{

    protected function createFileUpload(string $tmpName, string $name = null): FileUpload
    {
        $testResourceDir = dirname(__DIR__, 3) . '/test_resources';
        $path = "$testResourceDir/$tmpName";
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File '$tmpName' not found in '$testResourceDir'");
        }
        if (!$name) {
            $name = $tmpName;
        }

        return new FileUpload([
            'name' => $name ?: $tmpName,
            'size' => filesize($path),
            'tmp_name' => $path,
            'error' => UPLOAD_ERR_OK,
        ]);
    }
}
