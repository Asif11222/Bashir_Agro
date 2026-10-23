<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AppAppearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppHeaderController extends Controller
{
    public function update(Request $request, string $screen): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless(array_key_exists($screen, AppAppearanceService::SCREENS), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:90'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'title_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/D'],
            'font_size' => ['required', 'integer', 'between:16,26'],
            'alignment' => ['required', Rule::in(['left','center','right'])],
            'shadow' => ['sometimes', 'boolean'],
            'overlay' => ['required', 'numeric', 'between:0.15,0.7'],
            'image' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:3072'],
            'remove_image' => ['sometimes','boolean'],
        ]);

        $key = 'app_header.'.$screen;
        $original = Setting::get($key, []);
        $original = is_array($original) ? $original : [];
        $previous = $original['image_path'] ?? null;
        $image = $previous;
        $newFile = null;
        if ($request->boolean('remove_image')) {
            $image = null;
        } elseif ($request->hasFile('image')) {
            $newFile = $request->file('image')->store('app-headers', 'public');
            abort_unless($newFile, 500, 'হেডার ছবি সংরক্ষণ করা যায়নি।');
            $image = $newFile;
        }
        try {
            Setting::set($key, [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? '',
                'title_color' => strtoupper($data['title_color']),
                'font_size' => (int) $data['font_size'],
                'alignment' => $data['alignment'],
                'shadow' => $request->boolean('shadow'),
                'overlay' => (float) $data['overlay'],
                'image_path' => $image,
            ], 'app_appearance', 'Mobile header '.$screen);
        } catch (\Throwable $exception) {
            if ($newFile) Storage::disk('public')->delete($newFile);
            throw $exception;
        }
        if ($previous && $previous !== $image) {
            Storage::disk('public')->delete($previous);
        }
        return redirect()->to(route('admin.settings').'#mobile-headers')
            ->with('success', 'অ্যাপ হেডারের পরিবর্তন সংরক্ষণ করা হয়েছে।');
    }
}
