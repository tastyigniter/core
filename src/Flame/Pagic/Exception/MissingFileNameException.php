<?php

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
    public function setModel(string $model)
    {
        $this->model = $model;

        $this->message = "No file name attribute (fileName) specified for model [{$model}].";

        return $this;
    }

    /**
     * Get the affected Halcyon model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
