<?php

namespace Tests\Feature\Marketplace;

use App\Models\PrinterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinterProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_printers_index(): void
    {
        $this->get(route('printers.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_view_printers_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('printers.index'))
            ->assertOk()
            ->assertSee(__('Мої принтери'));
    }

    public function test_user_can_add_printer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('printers.store'), [
                'name' => 'Test Printer',
                'technology' => 'fdm',
                'bed_x' => 220,
                'bed_y' => 220,
                'bed_z' => 250,
                'nozzle' => 0.4,
                'materials' => ['PLA', 'PETG'],
            ])
            ->assertRedirect(route('printers.index'));

        $this->assertDatabaseHas('printer_profiles', [
            'user_id' => $user->id,
            'name' => 'Test Printer',
            'technology' => 'fdm',
            'is_default' => true,
        ]);

        $this->assertSame(1, PrinterProfile::where('user_id', $user->id)->count());
    }
}
