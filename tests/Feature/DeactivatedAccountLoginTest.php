<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedAccountLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_cannot_login_and_sees_support_email(): void
    {
        config(['app.support_email' => 'tandang@gmail.com']);

        foreach (['talenta_muda', 'penyedia_proyek'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => false]);

            $this->postJson('/web-login', ['email' => $user->email, 'password' => 'password'])
                ->assertForbidden()
                ->assertJsonPath('reason', 'account_inactive')
                ->assertJsonPath('support_email', 'tandang@gmail.com')
                ->assertJsonPath('message', 'Akun Anda dinonaktifkan oleh Administrator. Untuk informasi lebih lanjut atau pengajuan aktivasi kembali, hubungi kami di tandang@gmail.com.');

            $this->assertGuest();
        }
    }

    public function test_wrong_password_does_not_reveal_account_status(): void
    {
        $user = User::factory()->create(['role' => 'talenta_muda', 'is_active' => false]);

        $this->postJson('/web-login', ['email' => $user->email, 'password' => 'salah-total'])
            ->assertStatus(422)
            ->assertJsonMissingPath('support_email');
    }
}
