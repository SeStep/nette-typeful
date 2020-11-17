<?php declare(strict_types=1);

namespace SeStep\NetteTypeful\Types;

class FileDelete
{
    /** @var string */
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
