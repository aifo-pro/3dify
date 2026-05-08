<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Http\Request;

class TipsAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $q = Tip::with('user', 'author', 'product')->latest();
        if (in_array($status, ['pending', 'paid', 'refunded'], true)) {
            $q->where('status', $status);
        }
        $tips = $q->paginate(40)->withQueryString();

        $totals = [
            'count' => Tip::count(),
            'paid_amount' => (float) Tip::where('status', 'paid')->sum('amount'),
            'paid_count' => Tip::where('status', 'paid')->count(),
            'authors' => Tip::distinct('author_id')->count('author_id'),
        ];

        return view('admin.tips.index', compact('tips', 'totals', 'status'));
    }
}
