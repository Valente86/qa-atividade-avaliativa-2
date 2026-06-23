<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_index_returns_view_with_all_users(): void {
        User::factory()->count(3)->create();
        $this->get('/users')->assertStatus(200);
    }

    public function test_index_returns_empty_when_no_users(): void {
        $this->get('/users')->assertStatus(200);
    }

    public function test_create_shows_form(): void {
        $this->get('/users/create')->assertStatus(200);
    }

    public function test_store_creates_user_with_valid_data(): void {
        $data = ['name' => 'João', 'email' => 'joao@example.com', 'password' => 'pass123'];
        $this->post('/users', $data)->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
    }

    public function test_store_fails_with_missing_name(): void {
        $data = ['email' => 'test@example.com', 'password' => 'pass'];
        $this->post('/users', $data)->assertRedirect('/users/create');
    }

    public function test_store_fails_with_duplicate_email(): void {
        User::factory()->create(['email' => 'dup@example.com']);
        $data = ['name' => 'User', 'email' => 'dup@example.com', 'password' => 'pass'];
        $this->post('/users', $data)->assertRedirect('/users/create');
    }

    public function test_store_hashes_password(): void {
        $this->post('/users', ['name' => 'A', 'email' => 'a@b.com', 'password' => 'plain']);
        $user = User::where('email', 'a@b.com')->first();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('plain', $user->password));
    }

    public function test_show_returns_user_data(): void {
        $user = User::factory()->create();
        $this->get("/users/{$user->id}")->assertStatus(200);
    }

    public function test_show_redirects_when_user_not_found(): void {
        $this->get('/users/999')->assertRedirect('/users');
    }

    public function test_edit_shows_form_with_user_data(): void {
        $user = User::factory()->create();
        $this->get("/users/{$user->id}/edit")->assertStatus(200);
    }

    public function test_edit_redirects_when_user_not_found(): void {
        $this->get('/users/999/edit')->assertRedirect('/users');
    }

    public function test_update_modifies_user_data(): void {
        $user = User::factory()->create(['name' => 'Old', 'email' => 'old@a.com']);
        $this->put("/users/{$user->id}", ['name' => 'New', 'email' => 'new@a.com']);
        $this->assertDatabaseHas('users', ['name' => 'New']);
    }

    public function test_update_with_partial_data(): void {
        $user = User::factory()->create(['name' => 'Original', 'email' => 'orig@example.com']);
        $this->put("/users/{$user->id}", ['name' => 'Updated Name']);
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('orig@example.com', $user->email);
    }

    public function test_update_redirects_when_user_not_found(): void {
        $this->put('/users/999', ['name' => 'New'])->assertRedirect('/users');
    }

    public function test_update_fails_with_duplicate_email(): void {
        User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $this->put("/users/{$user2->id}", ['email' => 'user1@example.com'])
             ->assertRedirect("/users/{$user2->id}/edit");
    }

    public function test_destroy_deletes_user(): void {
        $user = User::factory()->create();
        $this->delete("/users/{$user->id}")->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_redirects_when_user_not_found(): void {
        $this->delete('/users/999')->assertRedirect('/users');
    }

    public function test_destroy_removes_user_from_database(): void {
        $user = User::factory()->create();
        $this->delete("/users/{$user->id}");
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}