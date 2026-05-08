<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function users(Request $request): StreamedResponse
    {
        $columns = ['id', 'name', 'username', 'email', 'role', 'is_suspended', 'manual_verification', 'email_verified_at', 'created_at'];
        return $this->stream(
            'users-'.now()->format('Y-m-d').'.csv',
            $columns,
            User::query()->orderBy('id')
                ->cursor()
                ->map(fn (User $u) => [
                    $u->id, $u->name, $u->username, $u->email, $u->role,
                    $u->is_suspended ? 1 : 0,
                    $u->manual_verification ? 1 : 0,
                    optional($u->email_verified_at)->toDateTimeString(),
                    optional($u->created_at)->toDateTimeString(),
                ])
        );
    }

    public function orders(Request $request): StreamedResponse
    {
        $columns = ['id', 'user_id', 'user_email', 'status', 'total', 'currency', 'created_at', 'paid_at'];
        return $this->stream(
            'orders-'.now()->format('Y-m-d').'.csv',
            $columns,
            Order::with('user')->orderBy('id')->cursor()
                ->map(fn (Order $o) => [
                    $o->id, $o->user_id, optional($o->user)->email,
                    $o->status, $o->total, $o->currency,
                    optional($o->created_at)->toDateTimeString(),
                    optional($o->paid_at)->toDateTimeString(),
                ])
        );
    }

    public function payments(Request $request): StreamedResponse
    {
        $columns = ['id', 'order_id', 'provider', 'status', 'amount', 'currency', 'created_at'];
        return $this->stream(
            'payments-'.now()->format('Y-m-d').'.csv',
            $columns,
            Payment::query()->orderBy('id')->cursor()
                ->map(fn (Payment $p) => [
                    $p->id, $p->order_id, $p->provider, $p->status, $p->amount, $p->currency,
                    optional($p->created_at)->toDateTimeString(),
                ])
        );
    }

    public function payouts(Request $request): StreamedResponse
    {
        $columns = ['id', 'author_id', 'author_email', 'amount', 'currency', 'status', 'method', 'requested_at', 'processed_at'];
        return $this->stream(
            'payouts-'.now()->format('Y-m-d').'.csv',
            $columns,
            Payout::with('author')->orderBy('id')->cursor()
                ->map(fn (Payout $p) => [
                    $p->id, $p->author_id, optional($p->author)->email,
                    $p->amount, $p->currency, $p->status, $p->method,
                    optional($p->requested_at)->toDateTimeString(),
                    optional($p->processed_at)->toDateTimeString(),
                ])
        );
    }

    private function stream(string $filename, array $columns, iterable $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        return $response;
    }
}
