<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComment;
use App\Models\ProductMake;
use App\Models\ProductReport;
use App\Models\ProductReview;
use App\Models\RefundRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModerationController extends Controller
{
    /** Hub with counts of every moderation queue. */
    public function hub()
    {
        $counts = [
            'products' => Product::where('status', 'pending')->count(),
            'reports' => ProductReport::where('status', 'pending')->count(),
            'reviews' => ProductReview::where('status', 'pending')->count(),
            'comments' => ProductComment::where('status', 'pending')->count(),
            'makes' => ProductMake::where('status', 'pending')->count(),
            'refunds' => RefundRequest::where('status', 'pending')->count(),
        ];

        $recent = [
            'reports' => ProductReport::with('product', 'user')->latest()->limit(5)->get(),
            'reviews' => ProductReview::with('product', 'user')->latest()->limit(5)->get(),
            'comments' => ProductComment::with('product', 'user')->latest()->limit(5)->get(),
            'makes' => ProductMake::with('product', 'user')->latest()->limit(5)->get(),
        ];

        return view('admin.moderation.hub', compact('counts', 'recent'));
    }

    // -------------------- REPORTS -----------------------------------------
    public function reports(Request $request)
    {
        $status = $request->input('status', 'pending');
        $query = ProductReport::query()->with('product.author', 'user')->latest();
        if (in_array($status, ['pending', 'reviewed', 'dismissed', 'actioned'], true)) {
            $query->where('status', $status);
        }
        $reports = $query->paginate(25)->withQueryString();
        $counts = $this->statusCounts(ProductReport::class, ['pending', 'reviewed', 'dismissed', 'actioned']);

        return view('admin.moderation.reports', compact('reports', 'status', 'counts'));
    }

    public function updateReport(Request $request, ProductReport $report, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'reviewed', 'dismissed', 'actioned'])],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $report->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? $report->admin_notes,
            'reviewed_at' => $data['status'] !== 'pending' ? now() : null,
        ]);
        $audit->record('report.update', $report, ['status' => $data['status']]);
        return back()->with('status', __('Скаргу оновлено.'));
    }

    // -------------------- REVIEWS -----------------------------------------
    public function reviews(Request $request)
    {
        $status = $request->input('status', 'pending');
        $rating = $request->input('rating');
        $query = ProductReview::query()->with('product', 'user')->latest();
        if (in_array($status, ['pending', 'published', 'hidden'], true)) {
            $query->where('status', $status);
        }
        if (is_numeric($rating)) {
            $query->where('rating', (int) $rating);
        }

        $reviews = $query->paginate(25)->withQueryString();
        $counts = $this->statusCounts(ProductReview::class, ['pending', 'published', 'hidden']);

        return view('admin.moderation.reviews', compact('reviews', 'status', 'counts'));
    }

    public function updateReview(Request $request, ProductReview $review, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'published', 'hidden'])],
        ]);
        $review->update(['status' => $data['status']]);
        $audit->record('review.moderate', $review, ['status' => $data['status']]);
        return back()->with('status', __('Рев\'ю оновлено.'));
    }

    public function destroyReview(ProductReview $review, AuditLogger $audit)
    {
        $audit->record('review.delete', $review);
        $review->delete();
        return back()->with('status', __('Рев\'ю видалено.'));
    }

    // -------------------- COMMENTS ----------------------------------------
    public function comments(Request $request)
    {
        $status = $request->input('status', 'pending');
        $query = ProductComment::query()->with('product', 'user')->latest();
        if (in_array($status, ['pending', 'published', 'hidden'], true)) {
            $query->where('status', $status);
        }
        $comments = $query->paginate(30)->withQueryString();
        $counts = $this->statusCounts(ProductComment::class, ['pending', 'published', 'hidden']);

        return view('admin.moderation.comments', compact('comments', 'status', 'counts'));
    }

    public function updateComment(Request $request, ProductComment $comment, AuditLogger $audit)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'published', 'hidden'])]]);
        $comment->update(['status' => $data['status']]);
        $audit->record('comment.moderate', $comment, ['status' => $data['status']]);
        return back()->with('status', __('Коментар оновлено.'));
    }

    public function destroyComment(ProductComment $comment, AuditLogger $audit)
    {
        $audit->record('comment.delete', $comment);
        $comment->delete();
        return back()->with('status', __('Коментар видалено.'));
    }

    // -------------------- MAKES -------------------------------------------
    public function makes(Request $request)
    {
        $status = $request->input('status', 'pending');
        $query = ProductMake::query()->with('product', 'user')->latest();
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        $makes = $query->paginate(24)->withQueryString();
        $counts = $this->statusCounts(ProductMake::class, ['pending', 'approved', 'rejected']);

        return view('admin.moderation.makes', compact('makes', 'status', 'counts'));
    }

    public function updateMake(Request $request, ProductMake $make, AuditLogger $audit)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['pending', 'approved', 'rejected'])]]);
        $make->update(['status' => $data['status']]);
        $audit->record('make.moderate', $make, ['status' => $data['status']]);
        return back()->with('status', __('Фото оновлено.'));
    }

    public function destroyMake(ProductMake $make, AuditLogger $audit)
    {
        $audit->record('make.delete', $make);
        if ($make->image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($make->image_path);
        }
        $make->delete();
        return back()->with('status', __('Фото видалено.'));
    }

    private function statusCounts(string $modelClass, array $statuses): array
    {
        $rows = $modelClass::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();
        $out = ['all' => array_sum($rows)];
        foreach ($statuses as $s) {
            $out[$s] = (int) ($rows[$s] ?? 0);
        }
        return $out;
    }
}
