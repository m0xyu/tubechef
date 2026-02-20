<?php

namespace App\Exceptions;

use App\Enums\Errors\VideoError;
use Exception;
use Illuminate\Http\JsonResponse;

class VideoException extends Exception
{
    public function __construct(protected VideoError $error)
    {
        parent::__construct(
            $error->message(),
            $error->status()
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->error->message(),
            'error_code' => $this->error->value,
        ], $this->error->status());
    }
}
