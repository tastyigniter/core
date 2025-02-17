<?php

declare(strict_types=1);

namespace Igniter\Flame\Pagic\Exception;

use RuntimeException;

class MissingFileNameException extends RuntimeException
{
    /**
     * Name of the affected Halcyon model.
     */
    protected string $model;

    /**
     * Set the affected Halcyon model.
     */
    public function setModel(string $model): static
    {
        $this->model = $model;

        $this->message = "No file name attribute (fileName) specified for model [{$model}].";

        return $this;
    }
}
