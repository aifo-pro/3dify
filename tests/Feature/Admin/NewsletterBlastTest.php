<?php

namespace Tests\Feature\Admin;

use App\Mail\NewsletterBlastMailable;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterBlastTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_blast_sends_to_users_and_active_subscribers_only(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);
        $user = User::factory()->create(['email' => 'buyer@example.test']);
        $unsubscribedUser = User::factory()->create(['email' => 'silent@example.test']);

        NewsletterSubscriber::query()->create([
            'email' => 'silent@example.test',
            'name' => 'Silent User',
            'source' => 'footer',
            'unsubscribed_at' => now(),
        ]);

        NewsletterSubscriber::query()->create([
            'email' => 'footer@example.test',
            'name' => 'Footer Subscriber',
            'source' => 'footer',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.newsletter.blast'), [
                'subject' => 'Marketplace update',
                'body' => '<p>Fresh models are live.</p>',
                'audience' => 'all_subscribers',
                'template_key' => 'weekly_digest',
                'confirm' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'buyer@example.test',
            'source' => 'user',
            'unsubscribed_at' => null,
        ]);

        Mail::assertQueued(NewsletterBlastMailable::class, 3);
        Mail::assertQueued(NewsletterBlastMailable::class, fn (NewsletterBlastMailable $mail) => $mail->subscriber->email === $admin->email);
        Mail::assertQueued(NewsletterBlastMailable::class, fn (NewsletterBlastMailable $mail) => $mail->subscriber->email === $user->email);
        Mail::assertQueued(NewsletterBlastMailable::class, fn (NewsletterBlastMailable $mail) => $mail->subscriber->email === 'footer@example.test');
        Mail::assertNotQueued(NewsletterBlastMailable::class, fn (NewsletterBlastMailable $mail) => $mail->subscriber->email === $unsubscribedUser->email);
    }
}
