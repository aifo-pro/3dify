<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product, public ProductReport $report) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $reasons = ProductReport::REASONS;
        $reasonLabel = $reasons[$this->report->reason] ?? $this->report->reason;

        return [
            'type' => 'report.created',
            'title' => __('Нова скарга №:id', ['id' => $this->report->id]),
            'message' => __('Користувач поскаржився на ":title" — причина: :reason.', [
                'title' => $this->product->localized('title'),
                'reason' => __($reasonLabel),
            ]),
            'url' => route('admin.moderation.reports').'#report-'.$this->report->id,
            'icon' => 'alert-triangle',
            'product_id' => $this->product->id,
            'report_id' => $this->report->id,
        ];
    }
}
