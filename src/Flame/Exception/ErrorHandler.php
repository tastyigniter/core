<?php

namespace Igniter\Flame\Exception;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorHandler
{
    /**
     * A list of the exception types that should not be reported.
     */
    protected array $dontReport = [
        AjaxException::class,
        ApplicationException::class,
        ModelNotFoundException::class,
        HttpException::class,
        ValidationException::class,
    ];

    /**
     * All of the register exception handlers.
     */
    protected array $handlers = [];

    public function __construct(ExceptionHandler $handler)
    {
        if (method_exists($handler, 'map')) {
            $handler->map(TokenMismatchException::class, function(TokenMismatchException $e) {
                return (new FlashException(
                    lang('igniter::admin.alert_invalid_csrf_token'), 'danger', 419, $e
                ))->important()->overlay()->actionUrl(url()->current());
            });
        }

        if (method_exists($handler, 'reportable')) {
            $handler->reportable(function(Throwable $ex) {
                return $this->report($ex);
            });
        }

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function(Throwable $ex) {
                return $this->render(request(), $ex);
            });
        }
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): ?bool
    {
        if (!class_exists('Event')) {
            return null;
        }

        /**
         * @event exception.beforeReport
         * Fires before the exception has been reported
         *
         * Example usage (prevents the reporting of a given exception)
         *
         *     Event::listen('exception.report', function (\Exception $exception) {
         *         if ($exception instanceof \My\Custom\Exception) {
         *             return false;
         *         }
         *     });
         */
        if (Event::dispatch('exception.beforeReport', [$e], true) === false) {
            return null;
        }

        if ($this->shouldntReport($e)) {
            return false;
        }

        /**
         * @event exception.report
         * Fired after the exception has been reported
         *
         * Example usage (performs additional reporting on the exception)
         *
         *     Event::listen('exception.report', function (\Exception $exception) {
         *         app('sentry')->captureException($exception);
         *     });
         */
        Event::dispatch('exception.report', [$e]);

        return null;
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(Request $request, Throwable $e): mixed
    {
        if (!class_exists('Event')) {
            return null;
        }

        $statusCode = $this->getStatusCode($e);

        if ($event = Event::dispatch('exception.beforeRender', [$e, $statusCode, $request], true)) {
            return Response::make($event, $statusCode);
        }

        return null;
    }

    /**
     * Determine if the exception is in the "do not report" list.
     */
    protected function shouldntReport(Throwable $e): bool
    {
        return !is_null(Arr::first($this->dontReport, fn($type) => $e instanceof $type));
    }

    /**
     * Checks if the exception implements the HttpExceptionInterface, or returns
     * as generic 500 error code for a server side error.
     */
    protected function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        } elseif ($exception instanceof AjaxException) {
            $code = 406;
        } else {
            $code = 500;
        }

        return $code;
    }
}
