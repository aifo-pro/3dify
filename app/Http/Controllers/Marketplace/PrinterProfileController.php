<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrinterProfileController extends Controller
{
    public function index()
    {
        return redirect()->route('profile.edit');
    }

    public function store(Request $request)
    {
        return back()->with('status', __('Профілі принтерів поки недоступні.'));
    }

    public function update(Request $request, int $printer)
    {
        return back()->with('status', __('Профілі принтерів поки недоступні.'));
    }

    public function destroy(int $printer)
    {
        return back()->with('status', __('Профілі принтерів поки недоступні.'));
    }

    public function makeDefault(int $printer)
    {
        return back()->with('status', __('Профілі принтерів поки недоступні.'));
    }
}
