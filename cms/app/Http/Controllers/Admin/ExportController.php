<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PublicationService;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function __construct(private PublicationService $publications) {}

    public function json(): JsonResponse
    {
        return response()->json($this->publications->buildDraftPayload(), 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
