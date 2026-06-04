<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\LegalPage;
use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\Translation;
use App\Services\EmailTemplateCatalog;
use App\Services\EmailTemplateRenderer;
use App\Services\SiteSettings;
use App\Mail\RenderedTemplateMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContentController extends Controller
{
    public function edit(Request $request)
    {
        $tab = (string) $request->query('tab', 'general');

        return view('admin.content', [
            'tab' => $tab,
            'settings' => Setting::orderBy('group')->orderBy('key')->get()->keyBy('key'),
            'seoPages' => SeoPage::orderBy('route_name')->get(),
            'translations' => Translation::orderBy('locale')->orderBy('key')->paginate(30, ['*'], 'translations_page')->withQueryString(),
            'emailTemplates' => EmailTemplate::orderBy('key')->orderBy('locale')->get(),
            'legalPages' => LegalPage::orderBy('slug')->orderBy('locale')->get(),
            'legalSlugs' => LegalPage::defaultSlugs(),
            'emailPlaceholderMap' => EmailTemplateRenderer::placeholderMap(),
            'emailTemplateCatalog' => EmailTemplateCatalog::templates(),
            'emailTypes' => EmailTemplateCatalog::labels(),
        ]);
    }

    /**
     * Placeholders supported by {@see \App\Services\EmailTemplateRenderer} per template key.
     *
     * @return array<string, list<string>>
     */
    public static function emailPlaceholderMap(): array
    {
        return EmailTemplateRenderer::placeholderMap();
    }

    /**
     * Save a single setting (legacy single-key form, still used).
     */
    public function setting(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'string'],
            'key' => ['required', 'string'],
            'value' => ['nullable', 'string'],
            'asset' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,ico,webp', 'max:2048'],
        ]);

        $value = $request->hasFile('asset')
            ? $request->file('asset')->store('brand', 'public')
            : ($data['value'] ?? null);

        Setting::updateOrCreate(['key' => $data['key']], ['group' => $data['group'], 'value' => $value]);
        app(SiteSettings::class)->forget($data['key']);

        return back()->with('status', 'Налаштування збережено.');
    }

    /**
     * Bulk save: arrays of settings and assets per section.
     */
    public function bulkSettings(Request $request)
    {
        $request->validate([
            'group' => ['required', 'string', 'max:60'],
            'tab' => ['nullable', 'string', 'max:60'],
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable'],
            'lists' => ['nullable', 'array'],
            'lists.*' => ['nullable', 'array'],
            'assets' => ['nullable', 'array'],
            'assets.*' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,ico,webp', 'max:8192'],
        ]);

        $group = $request->input('group');
        $tab = $request->input('tab');
        $settingsInput = (array) $request->input('settings', []);

        if ($group === 'mail') {
            $settingsInput = $this->normalizeMailSettings($settingsInput);
        }

        // Plain key/value settings (strings, numbers, booleans).
        foreach ($settingsInput as $key => $value) {
            if ($group === 'mail' && $key === 'mail.password' && ($value === '' || $value === null)) {
                continue;
            }

            if ($value === '' || $value === null) {
                $value = null;
            } elseif ($value === '1') {
                $value = true;
            } elseif ($value === '0') {
                $value = false;
            }

            Setting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $value]);
            app(SiteSettings::class)->forget($key);
        }

        // List-style settings (e.g. supported_languages[]).
        foreach ((array) $request->input('lists', []) as $key => $values) {
            Setting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => array_values((array) $values)]);
            app(SiteSettings::class)->forget($key);
        }

        // File uploads.
        if ($request->hasFile('assets')) {
            foreach ((array) $request->file('assets') as $key => $file) {
                if (! $file) {
                    continue;
                }

                $path = $file->store('brand', 'public');
                Setting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $path]);
                app(SiteSettings::class)->forget($key);
            }
        }

        return redirect()
            ->to(route('admin.content', $tab ? ['tab' => $tab] : []))
            ->with('status', 'Налаштування збережено.');
    }

    private function normalizeMailSettings(array $settings): array
    {
        $port = (int) ($settings['mail.port'] ?? 0);
        $encryption = strtolower(trim((string) ($settings['mail.encryption'] ?? '')));

        if ($port === 587 && in_array($encryption, ['ssl', 'smtps'], true)) {
            $settings['mail.encryption'] = 'tls';
        }

        if ($port === 465 && in_array($encryption, ['tls', 'starttls'], true)) {
            $settings['mail.encryption'] = 'ssl';
        }

        return $settings;
    }

    public function deleteAsset(Request $request)
    {
        $request->validate([
            'key' => ['required', 'string'],
            'tab' => ['nullable', 'string'],
        ]);

        $setting = Setting::where('key', $request->input('key'))->first();

        if ($setting) {
            $value = $setting->value;
            if (is_string($value)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($value);
            }
            $setting->delete();
            app(SiteSettings::class)->forget($request->input('key'));
        }

        return redirect()
            ->to(route('admin.content', $request->input('tab') ? ['tab' => $request->input('tab')] : []))
            ->with('status', 'Файл видалено.');
    }

    public function seo(Request $request)
    {
        $data = $request->validate([
            'route_name' => ['required', 'string'],
            'locale' => ['required', 'string', 'max:8'],
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        SeoPage::updateOrCreate(
            ['route_name' => $data['route_name'], 'locale' => $data['locale']],
            $data
        );

        return redirect()->to(route('admin.content', ['tab' => 'seo']))->with('status', 'SEO збережено.');
    }

    public function deleteSeo(SeoPage $seoPage)
    {
        $seoPage->delete();

        return redirect()->to(route('admin.content', ['tab' => 'seo']))->with('status', 'SEO-сторінку видалено.');
    }

    public function translation(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:translations,id'],
            'locale' => ['required', 'string', 'max:8'],
            'group' => ['required', 'string', 'max:60'],
            'key' => ['required', 'string', 'max:160'],
            'value' => ['nullable', 'string'],
        ]);

        if (! empty($data['id'])) {
            Translation::whereKey($data['id'])->update([
                'locale' => $data['locale'],
                'group' => $data['group'],
                'key' => $data['key'],
                'value' => $data['value'] ?? null,
            ]);
        } else {
            Translation::updateOrCreate(
                ['locale' => $data['locale'], 'group' => $data['group'], 'key' => $data['key']],
                ['value' => $data['value'] ?? null]
            );
        }

        return redirect()->to(route('admin.content', ['tab' => 'translations']))->with('status', 'Переклад збережено.');
    }

    public function deleteTranslation(Translation $translation)
    {
        $translation->delete();

        return redirect()->to(route('admin.content', ['tab' => 'translations']))->with('status', 'Переклад видалено.');
    }

    public function email(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'key' => ['required', 'string', 'max:60'],
            'locale' => ['required', 'string', 'max:8'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['id'])) {
            EmailTemplate::whereKey($data['id'])->update([
                'key' => $data['key'],
                'locale' => $data['locale'],
                'subject' => $data['subject'],
                'body' => $data['body'],
                'is_active' => $request->boolean('is_active', true),
            ]);
        } else {
            EmailTemplate::updateOrCreate(
                ['key' => $data['key'], 'locale' => $data['locale']],
                [
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'is_active' => $request->boolean('is_active', true),
                ]
            );
        }

        return redirect()->to(route('admin.content', ['tab' => 'email_templates']))->with('status', 'Шаблон email збережено.');
    }

    public function deleteEmail(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->to(route('admin.content', ['tab' => 'email_templates']))->with('status', 'Шаблон email видалено.');
    }

    public function sendTestEmail(Request $request)
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
        ]);

        try {
            $html = '<div style="font-family:Arial,sans-serif;background:#0b0f19;padding:32px;color:#e5e7eb;">'
                .'<div style="max-width:560px;margin:0 auto;background:#111827;border:1px solid #1f2937;border-radius:20px;padding:32px;">'
                .'<p style="display:inline-block;margin:0 0 18px;padding:7px 12px;border-radius:999px;background:#34d399;color:#03130d;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;">3Dify SMTP</p>'
                .'<h1 style="margin:0 0 12px;color:#fff;font-size:28px;">SMTP test email</h1>'
                .'<p style="margin:0;color:#9ca3af;font-size:15px;line-height:1.7;">If you see this email as HTML, Laravel SMTP is configured correctly.</p>'
                .'<p style="margin:24px 0 0;color:#6b7280;font-size:13px;">Sent at '.e(now()->toDateTimeString()).'</p>'
                .'</div></div>';

            Mail::to($data['to'])->send(new RenderedTemplateMail('3Dify - SMTP test', $html));

            return redirect()->to(route('admin.content', ['tab' => 'mail']))->with('status', 'Тестовий HTML-лист надіслано на '.$data['to']);
        } catch (\Throwable $e) {
            return redirect()->to(route('admin.content', ['tab' => 'mail']))->withErrors(['mail' => 'Помилка надсилання: '.$e->getMessage()]);
        }
    }
}
