<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()
            ->notifications()
            ->latest()
            ->paginate(25);

        return view('marketplace.notifications.index', [
            'items' => $items,
        ]);
    }

    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        if ($url = data_get($notification->data, 'url')) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', __('Усі сповіщення прочитано.'));
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->delete();

        return back();
    }
}
