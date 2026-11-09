<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\ResumeVersion;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ResumeController extends Controller
{
    public function edit(): View
    {
        return view('admin.resume.edit', [
            'versions' => ResumeVersion::query()->with('pdf')->latest()->get(),
            'current' => ResumeVersion::query()->where('is_current', true)->with('pdf')->first(),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'label' => ['nullable', 'string', 'max:190'],
        ]);

        $file = $data['pdf'];
        $name = 'cv-'.now()->format('Ymd-His').'.pdf';
        $path = $file->storeAs('media/drafts/cv', $name, 'local');

        $asset = MediaAsset::query()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => 'application/pdf',
            'size' => $file->getSize(),
            'is_published' => false,
            'used_by' => ['resume'],
        ]);

        ResumeVersion::query()->update(['is_current' => false]);
        ResumeVersion::query()->create([
            'label' => $data['label'] ?: $file->getClientOriginalName(),
            'source' => 'upload',
            'pdf_media_id' => $asset->id,
            'is_current' => true,
        ]);

        return back()->with('status', 'CV uploaded (draft). Publish to make it public.');
    }

    public function updateFields(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fields_en' => ['nullable', 'array'],
            'fields_fa' => ['nullable', 'array'],
        ]);

        $current = ResumeVersion::query()->where('is_current', true)->first();
        if (! $current) {
            $current = ResumeVersion::query()->create([
                'label' => 'Structured resume',
                'source' => 'fields',
                'is_current' => true,
            ]);
        }

        $current->fields_en = $data['fields_en'] ?? [];
        $current->fields_fa = $data['fields_fa'] ?? [];
        $current->save();

        return back()->with('status', 'Resume fields saved.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $locale = $request->validate(['locale' => ['required', 'in:en,fa']])['locale'];
        $current = ResumeVersion::query()->where('is_current', true)->first();
        $fields = $locale === 'fa' ? ($current?->fields_fa ?: []) : ($current?->fields_en ?: []);

        $html = view('admin.resume.pdf', [
            'locale' => $locale,
            'dir' => $locale === 'fa' ? 'rtl' : 'ltr',
            'fields' => $fields,
            'name' => $fields['name'] ?? 'Amirhossein Zamanzade',
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', $locale === 'fa' ? 'DejaVu Sans' : 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $name = 'cv-generated-'.$locale.'-'.now()->format('Ymd-His').'.pdf';
        $path = 'media/drafts/cv/'.$name;
        Storage::disk('local')->put($path, $dompdf->output());

        $asset = MediaAsset::query()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $name,
            'mime' => 'application/pdf',
            'size' => Storage::disk('local')->size($path),
            'is_published' => false,
            'used_by' => ['resume-generated'],
        ]);

        ResumeVersion::query()->update(['is_current' => false]);
        ResumeVersion::query()->create([
            'label' => 'Generated '.$locale.' CV',
            'source' => 'generated',
            'pdf_media_id' => $asset->id,
            'fields_en' => $current?->fields_en,
            'fields_fa' => $current?->fields_fa,
            'is_current' => true,
        ]);

        return back()->with('status', 'Generated '.$locale.' PDF (draft). Persian uses DejaVu Sans for reliable Dompdf glyph coverage; embed Vazirmatn TTF later if needed.');
    }

    public function activate(ResumeVersion $resume): RedirectResponse
    {
        ResumeVersion::query()->update(['is_current' => false]);
        $resume->is_current = true;
        $resume->save();

        return back()->with('status', 'Resume version activated (draft). Publish to update download link.');
    }
}
