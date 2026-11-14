<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $type = $request->query('type');
        $media = MediaAsset::query()->latest('id');
        if (is_string($search) && trim($search) !== '') {
            $media->where('original_name', 'like', '%'.trim($search).'%');
        }
        if ($type === 'images') {
            $media->where('mime', 'like', 'image/%');
        } elseif ($type === 'pdf') {
            $media->where('mime', 'application/pdf');
        }

        return view('admin.media.index', [
            'media' => $media->paginate(30)->withQueryString(),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:12288', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,application/pdf'],
            'alt_en' => ['nullable', 'string', 'max:190'],
            'alt_fa' => ['nullable', 'string', 'max:190'],
        ]);

        $file = $data['file'];
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        if (in_array($ext, ['php', 'phtml', 'phar', 'cgi', 'js', 'html', 'htm'], true)) {
            return back()->withErrors(['file' => 'Executable or script uploads are not allowed.']);
        }

        $isPdf = $file->getMimeType() === 'application/pdf';
        $folder = $isPdf ? 'media/drafts/cv' : 'media/drafts/img';
        $name = ($isPdf ? 'file-' : 'img-').now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;
        $path = $file->storeAs($folder, $name, 'local');

        MediaAsset::query()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt_en' => $data['alt_en'] ?? null,
            'alt_fa' => $data['alt_fa'] ?? null,
            'is_published' => false,
            'used_by' => [],
        ]);

        return back()->with('status', 'Media uploaded to private draft storage.');
    }

    public function update(Request $request, MediaAsset $media): RedirectResponse
    {
        $data = $request->validate([
            'alt_en' => ['nullable', 'string', 'max:190'],
            'alt_fa' => ['nullable', 'string', 'max:190'],
        ]);
        $media->fill($data)->save();

        return back()->with('status', 'Media metadata updated.');
    }

    public function raw(MediaAsset $media): StreamedResponse|BinaryFileResponse
    {
        if (! Storage::disk($media->disk)->exists($media->path)) {
            abort_unless($media->is_published && $media->public_path && is_file(public_path($media->public_path)), 404);

            return response()->file(public_path($media->public_path), ['X-Content-Type-Options' => 'nosniff']);
        }

        return Storage::disk($media->disk)->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
