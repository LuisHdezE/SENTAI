<?php

namespace Sentai\Modules\Identity\Presentation\Http\Web;

use Illuminate\Http\JsonResponse;

final class WebCsrfController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }
}
