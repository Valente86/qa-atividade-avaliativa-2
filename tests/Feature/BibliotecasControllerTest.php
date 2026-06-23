<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\Biblioteca;
use App\Models\User;

class BibliotecasControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_index_returns_view_with_all_bibliotecas(): void {
        Biblioteca::factory()->count(3)->create();
        $this->get('/bibliotecas')->assertStatus(200);
    }

    public function test_index_filters_bibliotecas_by_search_name(): void {
        Biblioteca::factory()->create(['nome' => 'Biblioteca Central']);
        $this->get('/bibliotecas?nome=Central')->assertStatus(200);
    }

    public function test_index_returns_empty_when_search_has_no_results(): void {
        $this->get('/bibliotecas?nome=NonExistent')->assertStatus(200);
    }

    public function test_create_shows_form_with_users(): void {
        $this->get('/bibliotecas/new')->assertStatus(200);
    }

    public function test_store_creates_biblioteca_with_valid_data(): void {
        $data = ['nome' => 'Biblio Nova', 'endereco' => 'Rua X', 'created_by' => $this->user->id];
        $this->post('/bibliotecas/create', $data)->assertRedirect('/bibliotecas');
        $this->assertDatabaseHas('bibliotecas', ['nome' => 'Biblio Nova']);
    }

    public function test_store_fails_with_missing_fields(): void {
        $data = ['endereco' => 'Apenas Endereco'];
        $this->post('/bibliotecas/create', $data)->assertRedirect('/bibliotecas/new');
    }

    public function test_store_creates_biblioteca_with_minimal_data(): void {
        $this->post('/bibliotecas/create', ['nome' => 'Minima', 'created_by' => $this->user->id])->assertRedirect('/bibliotecas');
    }

    public function test_edit_shows_form_with_biblioteca_data(): void {
        $b = Biblioteca::factory()->create();
        $this->get("/bibliotecas/edit/{$b->id}")->assertStatus(200);
    }

    public function test_edit_redirects_when_biblioteca_not_found(): void {
        $this->get('/bibliotecas/edit/999')->assertRedirect('/bibliotecas');
    }

    public function test_update_modifies_all_fields(): void {
        $b = Biblioteca::factory()->create();
        $this->put("/bibliotecas/update/{$b->id}", ['nome' => 'New Name'])->assertRedirect('/bibliotecas');
        $this->assertDatabaseHas('bibliotecas', ['nome' => 'New Name']);
    }

    public function test_update_modifies_only_provided_fields(): void {
        $b = Biblioteca::factory()->create(['nome' => 'Orig', 'endereco' => 'End']);
        $this->put("/bibliotecas/update/{$b->id}", ['nome' => 'Upd']);
        $b->refresh();
        $this->assertEquals('Upd', $b->nome);
        $this->assertEquals('End', $b->endereco);
    }

    public function test_update_returns_error_when_biblioteca_not_found(): void {
        $this->put('/bibliotecas/update/999', ['nome' => 'N'])->assertStatus(404);
    }

    public function test_update_ignores_empty_values(): void {
        $b = Biblioteca::factory()->create(['nome' => 'O']);
        $this->put("/bibliotecas/update/{$b->id}", ['nome' => '']);
        $b->refresh();
        $this->assertEquals('O', $b->nome);
    }

    public function test_destroy_deletes_biblioteca(): void {
        $b = Biblioteca::factory()->create();
        $this->delete("/bibliotecas/delete/{$b->id}")->assertRedirect('/bibliotecas');
    }

    public function test_destroy_returns_error_when_biblioteca_not_found(): void {
        $this->delete('/bibliotecas/delete/999')->assertStatus(404);
    }

    public function test_destroy_deletes_biblioteca_with_related_pessoas(): void {
        $b = Biblioteca::factory()->create();
        $this->delete("/bibliotecas/delete/{$b->id}");
        $this->assertDatabaseMissing('bibliotecas', ['id' => $b->id]);
    }
}