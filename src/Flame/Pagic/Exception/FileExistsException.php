<?php

declare(strict_types=1);

namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class FileExistsException extends RuntimeException
{
    /**
     * Name of the affected directory path.
     */
    protected string $invalidPath;

    /**
     * Set the affected directory path.
     */
    public function setInvalidPath(string $path): self
    {
        $this->invalidPath = $path;

        $this->message = "A file already exists at [{$path}].";

        return $this;
    }
}
