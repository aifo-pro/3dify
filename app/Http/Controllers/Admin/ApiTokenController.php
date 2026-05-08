<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = ApiToken::with('user:id,name,email')->latest()->limit(200)->get();
        return view('admin.api-tokens.index', compact('tokens'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'string', 'max:200'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $abilities = $data['abilities'] ? array_values(array_filter(array_map('trim', explode(',', $data['abilities'])))) : ['*'];
        $gen = ApiToken::generate();

        $token = ApiToken::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'token_hash' => $gen['hash'],
            'abilities' => $abilities,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $audit->record('api-token.create', $token);

        return redirect()->route('admin.api-tokens')->with('plain_token', $gen['plain'])->with('status', __('Токен створено. Збережіть його зараз — наступного разу ми його не покажемо.'));
    }

    public function destroy(ApiToken $token, AuditLogger $audit)
    {
        $audit->record('api-token.revoke', $token);
        $token->delete();
        return back()->with('status', __('Токен відкликано.'));
    }
}
