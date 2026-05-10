<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\PrinterProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrinterProfileController extends Controller
{
    public function index(Request $request)
    {
        $printers = $request->user()
            ->printers()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('marketplace.printers.index', [
            'printers' => $printers,
            'technologies' => PrinterProfile::TECHNOLOGIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPrinterPayload($request);

        $user = $request->user();
        $materials = ! empty($data['materials']) ? array_values($data['materials']) : null;

        $noPrintersYet = $user->printers()->count() === 0;
        $isDefault = $request->boolean('is_default') || $noPrintersYet;

        $printer = $user->printers()->create([
            'name' => $data['name'],
            'technology' => $data['technology'],
            'bed_x' => $data['bed_x'] ?? null,
            'bed_y' => $data['bed_y'] ?? null,
            'bed_z' => $data['bed_z'] ?? null,
            'nozzle' => $data['nozzle'] ?? null,
            'materials' => $materials,
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            $user->printers()->whereKeyNot($printer->id)->update(['is_default' => false]);
        }

        return redirect()
            ->route('printers.index')
            ->with('status', __('Принтер збережено.'));
    }

    public function update(Request $request, PrinterProfile $printer)
    {
        $this->authorizePrinter($request, $printer);

        $data = $this->validatedPrinterPayload($request);

        $user = $request->user();
        $materials = ! empty($data['materials']) ? array_values($data['materials']) : null;

        $isDefault = $request->boolean('is_default');

        $printer->update([
            'name' => $data['name'],
            'technology' => $data['technology'],
            'bed_x' => $data['bed_x'] ?? null,
            'bed_y' => $data['bed_y'] ?? null,
            'bed_z' => $data['bed_z'] ?? null,
            'nozzle' => $data['nozzle'] ?? null,
            'materials' => $materials,
            'is_default' => $isDefault,
        ]);

        if ($isDefault) {
            $user->printers()->whereKeyNot($printer->id)->update(['is_default' => false]);
        }

        if (! $user->printers()->where('is_default', true)->exists()) {
            $fallback = $user->printers()->first();
            if ($fallback) {
                $fallback->update(['is_default' => true]);
            }
        }

        return redirect()
            ->route('printers.index')
            ->with('status', __('Принтер оновлено.'));
    }

    public function destroy(Request $request, PrinterProfile $printer)
    {
        $this->authorizePrinter($request, $printer);

        $wasDefault = $printer->is_default;
        $printer->delete();

        if ($wasDefault) {
            $next = $request->user()->printers()->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()
            ->route('printers.index')
            ->with('status', __('Принтер видалено.'));
    }

    public function makeDefault(Request $request, PrinterProfile $printer)
    {
        $this->authorizePrinter($request, $printer);

        $request->user()->printers()->update(['is_default' => false]);
        $printer->update(['is_default' => true]);

        return redirect()
            ->route('printers.index')
            ->with('status', __('Основний принтер оновлено.'));
    }

    private function authorizePrinter(Request $request, PrinterProfile $printer): void
    {
        abort_if($printer->user_id !== $request->user()->id, 403);
    }

    /**
     * @return array{name: string, technology: string, bed_x?: int|null, bed_y?: int|null, bed_z?: int|null, nozzle?: float|null, materials?: array<int, string>|null}
     */
    private function validatedPrinterPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'technology' => ['required', 'string', Rule::in(array_keys(PrinterProfile::TECHNOLOGIES))],
            'bed_x' => ['nullable', 'integer', 'min:30', 'max:2000'],
            'bed_y' => ['nullable', 'integer', 'min:30', 'max:2000'],
            'bed_z' => ['nullable', 'integer', 'min:30', 'max:2000'],
            'nozzle' => ['nullable', 'numeric', 'min:0.1', 'max:2'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['string', 'max:40'],
        ]);
    }
}
