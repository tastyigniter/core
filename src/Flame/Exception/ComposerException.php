<?php

namespace Igniter\Flame\Exception;

use Composer\IO\IOInterface;
use Exception;
use Throwable;

class ComposerException extends Exception
{
    protected string $output;

    public function __construct(Throwable $ex, IOInterface $io)
    {
        $message = 'Error updating composer requirements: '.$ex->getMessage()."\nOutput: ".$io->getOutput();

        parent::__construct($message, $ex->getCode(), $ex->getPrevious());
        $this->output = $io->getOutput();
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
