<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentEntry;
use App\Models\ContentRevision;
use App\Models\Project;
use App\Models\Publication;
use App\Models\SeoEntry;
use App\Models\SiteState;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'dirtyContent' => ContentEntry::query()->where('is_dirty', true)->count(),
            'dirtyProjects' => Project::query()->where('is_dirty', true)->count(),
            'dirtySeo' => SeoEntry::query()->where('is_dirty', true)->count(),
            'recent' => ContentRevision::query()->latest()->limit(12)->get(),
            'publications' => Publication::query()->latest('published_at')->limit(20)->get(),
            'currentId' => SiteState::current()->current_publication_id,
            'projectCount' => Project::query()->active()->count(),
        ]);
    }

    public function preview(): RedirectResponse
    {
        return redirect('/?preview=1');
    }
}
