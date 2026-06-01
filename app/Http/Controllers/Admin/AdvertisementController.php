<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    public function index()
    {
        $ads = Advertisement::latest()->paginate(20);
        return view('admin.ads.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.ads.form', ['ad' => new Advertisement]);
    }

    public function store(Request $request)
    {
        $ad = Advertisement::create($this->validated($request));
        $this->handleImage($request, $ad);
        $this->clearCache();
        return redirect()->route('admin.ads.index')->with('status', 'Рекламу створено.');
    }

    public function edit(Advertisement $ad)
    {
        return view('admin.ads.form', compact('ad'));
    }

    public function update(Request $request, Advertisement $ad)
    {
        $ad->update($this->validated($request));
        $this->handleImage($request, $ad);
        $this->clearCache();
        return back()->with('status', 'Збережено.');
    }

    public function destroy(Advertisement $ad)
    {
        if ($ad->image_path) Storage::disk('public')->delete($ad->image_path);
        $ad->delete();
        $this->clearCache();
        return redirect()->route('admin.ads.index')->with('status', 'Видалено.');
    }

    public function toggle(Advertisement $ad)
    {
        $ad->update(['is_active' => ! $ad->is_active]);
        $this->clearCache();
        return back()->with('status', $ad->is_active ? 'Активована.' : 'Деактивована.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title_uk'    => ['required', 'string', 'max:120'],
            'title_en'    => ['nullable', 'string', 'max:120'],
            'desc_uk'     => ['nullable', 'string', 'max:300'],
            'badge_label' => ['nullable', 'string', 'max:30'],
            'target_url'  => ['required', 'url', 'max:500'],
            'ad_type'     => ['required', 'in:grid,banner,sidebar'],
            'grid_every'  => ['nullable', 'integer', 'min:2', 'max:100'],
            'pages'       => ['nullable', 'array'],
            'pages.*'     => ['in:catalog,category,home,search'],
            'is_active'   => ['nullable'],
            'starts_at'   => ['nullable', 'date'],
            'ends_at'     => ['nullable', 'date'],
        ]);

        return [
            'title'       => ['uk' => $data['title_uk'], 'en' => $data['title_en'] ?? $data['title_uk']],
            'description' => ['uk' => $data['desc_uk'] ?? '', 'en' => ''],
            'badge_label' => $data['badge_label'] ?: 'Реклама',
            'target_url'  => $data['target_url'],
            'ad_type'     => $data['ad_type'],
            'grid_every'  => (int) ($data['grid_every'] ?? 8),
            'pages'       => $data['pages'] ?? null,
            'is_active'   => $request->boolean('is_active'),
            'starts_at'   => $data['starts_at'] ?? null,
            'ends_at'     => $data['ends_at'] ?? null,
            'created_by'  => auth()->id(),
        ];
    }

    private function handleImage(Request $request, Advertisement $ad): void
    {
        if ($request->hasFile('image')) {
            if ($ad->image_path) Storage::disk('public')->delete($ad->image_path);
            $path = $request->file('image')->store('ads', 'public');
            $ad->update(['image_path' => $path]);
        }
    }

    private function clearCache(): void
    {
        foreach (Advertisement::PAGES as $page) {
            Cache::forget("ads.grid.{$page}");
            Cache::forget("ads.banner.{$page}");
            Cache::forget("ads.sidebar.{$page}");
        }
    }
}
