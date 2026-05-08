<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\User;
use App\Notifications\ProductReportSubmittedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', Rule::in(array_keys(ProductReport::REASONS))],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = ProductReport::create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'reason' => $data['reason'],
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);

        // Notify every admin / moderator so they see the report in their bell.
        $moderators = User::whereIn('role', ['admin', 'moderator'])->get();
        if ($moderators->isNotEmpty()) {
            Notification::send($moderators, new ProductReportSubmittedNotification($product, $report));
        }

        return back()->with('status', __('Скаргу №:id прийнято. Команда модерації перегляне її протягом 24 годин і повідомить про результат.', [
            'id' => $report->id,
        ]));
    }
}
