<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PortfolioContent;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function __construct(private PortfolioContent $content) {}

    public function json(): JsonResponse
    {
        return response()->json($this->content->payload(true), 200, ['Cache-Control' => 'private, no-store'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
