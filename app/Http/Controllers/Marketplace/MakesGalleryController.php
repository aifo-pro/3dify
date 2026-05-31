<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ProductMake;
use Illuminate\Http\Request;

class MakesGalleryController extends Controller
{
    public function __invoke(Request $request)
    {
        $makes = ProductMake::query()
            ->where('status', 'approved')
            ->with(['user', 'product' => fn ($q) => $q->withTrashed()])
            ->latest()
            ->paginate(24);

        return view('marketplace.makes-gallery', compact('makes'));
    }
}
