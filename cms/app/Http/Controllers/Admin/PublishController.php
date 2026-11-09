<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Services\PublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublishController extends Controller
{
    public function __construct(private PublicationService $publications) {}

    public function publish(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:190'],
        ]);

        $publication = $this->publications->publish($request->user(), $data['label'] ?? null);

        return back()->with('status', 'Published #'.$publication->id.' — public site updated atomically.');
    }

    public function rollback(Request $request, Publication $publication): RedirectResponse
    {
        $this->publications->rollback($publication, $request->user());

        return back()->with('status', 'Rolled back to publication #'.$publication->id);
    }
}
