<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'access_token',
        'capi_access_token',
        'webhook_verify_token',
        'app_secret',
        'client_secret',
        'api_key',
        'secret_key',
        'fb_pixel_api_key',
        'fb_test_event_code',
        'fb_app_secret',
        'gmail_secret_id',
        'captcha_secret_key',
        'tiktok_pixel_token',
        'tiktok_pixel_secret',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        if ($this->isMissingColumnException($e)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database schema is incomplete. Run the application migrations through the approved CLI maintenance process, then retry the request.',
                ], 503);
            }

            return redirect()->back()->with('error', 'Database schema is incomplete. Run the application migrations through the approved CLI maintenance process, then retry the request.');
        }

        return parent::render($request, $e);
    }

    private function isMissingColumnException(Throwable $e): bool
    {
        if (!($e instanceof QueryException)) {
            return false;
        }

        $message = $e->getMessage();
        return str_contains($message, 'SQLSTATE[42S22]') && str_contains($message, 'Column not found');
    }

}
