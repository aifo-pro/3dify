<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Tip;

class TipSuccessController extends Controller
{
    public function __invoke(Tip $tip)
    {
        abort_unless($tip->user_id === auth()->id(), 403);

        if ($tip->product) {
            return redirect()
                ->route('products.show', $tip->product)
                ->with('status', __('Дякуємо! Платіж обробляється. Після підтвердження AIFO подяка буде зарахована автору.'));
        }

        return redirect()
            ->route('dashboard')
            ->with('status', __('Дякуємо! Платіж обробляється. Після підтвердження AIFO подяка буде зарахована автору.'));
    }
}

