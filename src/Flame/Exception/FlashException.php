<?php

namespace Igniter\Flame\Exception;

use Exception;
use Igniter\Flame\Flash\FlashBag;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FlashException extends Exception implements HttpExceptionInterface
{
    protected bool $important = false;

    protected bool $overlay = false;

    protected ?string $title = null;

    protected bool $shouldReport = false;

    protected ?string $actionUrl = null;

    protected ?string $redirectUrl = null;

    protected ?Response $response = null;

    public function __construct($message, protected string $type = 'danger', $code = 406, ?Exception $previous = null)
    {
        $this->message = $message;

        parent::__construct($message, $code, $previous);
    }

    public static function alert(string $message, string $type = 'danger', int $statusCode = 406): self
    {
        return new static($message, $type, $statusCode);
    }

    public static function info(string $message, ?string $title = null, int $statusCode = 406): self
    {
        return (new static($message, 'info', $statusCode))->title($title);
    }

    public static function success(string $message, ?string $title = null, int $statusCode = 200): self
    {
        return (new static($message, 'success', $statusCode))->title($title);
    }

    public static function error(string $message, ?string $title = null, int $statusCode = 406): self
    {
        return (new static($message, 'danger', $statusCode))->title($title);
    }

    public static function warning(string $message, ?string $title = null, int $statusCode = 406): self
    {
        return (new static($message, 'warning', $statusCode))->title($title);
    }

    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function overlay(): self
    {
        $this->overlay = true;

        return $this;
    }

    public function important(): self
    {
        $this->important = true;

        return $this;
    }

    public function actionUrl(string $url): self
    {
        $this->actionUrl = $url;

        return $this;
    }

    public function redirectTo(string $url): self
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function shouldReport(): self
    {
        $this->shouldReport = true;

        return $this;
    }

    public function getContents(): array
    {
        return [
            'class' => $this->type,
            'title' => $this->title,
            'text' => $this->message,
            'important' => $this->important,
            'overlay' => $this->overlay,
            'actionUrl' => $this->actionUrl,
        ];
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function report(): ?bool
    {
        return $this->shouldReport ?: null;
    }

    public function render(Request $request): mixed
    {
        if (!is_null($this->redirectUrl)) {
            $this->toFlashBag();

            return redirect()->to($this->redirectUrl);
        }

        if ($this->response instanceof Response) {
            $this->toFlashBag()->now();

            return $this->response;
        }

        if ($request->expectsJson()) {
            return response([
                'X_IGNITER_FLASH_MESSAGES' => [$this->getContents()],
            ], $this->code);
        }

        if (!config('app.debug')) {
            if ($controller = $request->route()?->getController()) {
                return response($controller->makeView('flash_exception', $this->getContents()), 500);
            }
        }

        return false;
    }

    protected function toFlashBag(): FlashBag
    {
        $flashBag = flash($this->message, $this->type);
        if ($this->overlay) {
            $flashBag->overlay();
        }
        if ($this->important) {
            $flashBag->important();
        }

        return $flashBag;
    }
}
