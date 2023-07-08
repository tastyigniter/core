<?php

namespace Igniter\Flame\Exception;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AjaxException extends BaseException
{
    /**
     * @var array Collection response contents.
     */
    protected $contents;

    /**
     * Constructor.
     */
    public function __construct($contents, $code = 406)
    {
        if (is_string($contents)) {
            $contents = ['result' => $contents];
        }

        $this->contents = $contents;

        parent::__construct(json_encode($contents), $code);
    }

    /**
     * Returns invalid fields.
     */
    public function getContents()
    {
        return $this->contents;
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): Response
    {
        return response($this->getContents(), $this->code);
    }
}
