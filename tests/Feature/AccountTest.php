<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->create([
            'role' => Role::Guru,
            'password' => Hash::make('lama123'),
            'password_plain' => 'lama123',
        ]);
    }

    public function test_teacher_can_open_account_page(): void
    {
        $this->actingAs($this->teacher())->get(route('akun.edit'))->assertOk()->assertSee('Ganti Password');
    }

    public function test_teacher_can_change_own_password(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->put(route('akun.password'), [
            'current_password' => 'lama123',
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])->assertRedirect(route('akun.edit'));

        $teacher->refresh();
        $this->assertTrue(Hash::check('baru1234', $teacher->password));
        $this->assertNull($teacher->password_plain);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->put(route('akun.password'), [
            'current_password' => 'salah',
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('lama123', $teacher->fresh()->password));
    }

    public function test_teacher_can_upload_and_remove_photo(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();

        $this->actingAs($teacher)->post(route('akun.foto'), [
            'photo' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ])->assertRedirect();

        $path = $teacher->fresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($teacher)->delete(route('akun.foto.hapus'))->assertRedirect();
        $this->assertNull($teacher->fresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->teacher())->post(route('akun.foto'), [
            'photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_operator_and_headmaster_can_change_own_password(): void
    {
        foreach ([Role::Operator, Role::Kepala] as $role) {
            $user = User::factory()->create(['role' => $role, 'password' => Hash::make('lama123')]);

            $this->actingAs($user)->get(route('akun.edit'))->assertOk()->assertSee('Profil Saya');

            $this->actingAs($user)->put(route('akun.password'), [
                'current_password' => 'lama123',
                'password' => 'baru1234',
                'password_confirmation' => 'baru1234',
            ])->assertRedirect(route('akun.edit'));

            $this->assertTrue(Hash::check('baru1234', $user->fresh()->password));
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('akun.edit'))->assertRedirect(route('login'));
    }
}
