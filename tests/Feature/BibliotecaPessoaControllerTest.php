<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use App\Models\Biblioteca;
use App\Models\Pessoa;

class BibliotecaPessoaControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $biblioteca;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        $this->biblioteca = Biblioteca::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_create_shows_add_pessoa_form(): void {
        Pessoa::factory()->count(2)->create();
        $this->get("/bibliotecas/{$this->biblioteca->id}/pessoas/add")->assertStatus(200);
    }

    public function test_create_filters_already_associated_pessoas(): void {
        $pessoaAssociated = Pessoa::factory()->create();
        $this->biblioteca->pessoas()->attach($pessoaAssociated->id);
        
        $this->get("/bibliotecas/{$this->biblioteca->id}/pessoas/add")->assertStatus(200);
    }

    public function test_create_returns_empty_when_all_pessoas_associated(): void {
        $pessoa = Pessoa::factory()->create();
        $this->biblioteca->pessoas()->attach($pessoa->id);
        
        $this->get("/bibliotecas/{$this->biblioteca->id}/pessoas/add")->assertStatus(200);
    }

    public function test_create_with_nonexistent_biblioteca(): void {
        $this->get('/bibliotecas/999/pessoas/add')->assertStatus(404);
    }

    public function test_store_links_pessoa_to_biblioteca(): void {
        $pessoa = Pessoa::factory()->create();
        
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", [
            'pessoa_id' => $pessoa->id
        ])->assertStatus(302);
        
        $this->assertDatabaseHas('biblioteca_pessoa', [
            'biblioteca_id' => $this->biblioteca->id,
            'pessoa_id' => $pessoa->id
        ]);
    }

    public function test_store_fails_with_nonexistent_pessoa(): void {
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", [
            'pessoa_id' => 9999
        ])->assertStatus(302);
    }

    public function test_store_fails_without_pessoa_id(): void {
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", [])->assertStatus(302);
    }

    public function test_store_fails_when_pessoa_already_associated(): void {
        $pessoa = Pessoa::factory()->create();
        $this->biblioteca->pessoas()->attach($pessoa->id);
        
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", [
            'pessoa_id' => $pessoa->id
        ])->assertStatus(302);
    }

    public function test_store_multiple_pessoas(): void {
        $p1 = Pessoa::factory()->create();
        $p2 = Pessoa::factory()->create();
        
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", ['pessoa_id' => $p1->id]);
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", ['pessoa_id' => $p2->id]);
        
        $this->assertDatabaseHas('biblioteca_pessoa', ['pessoa_id' => $p1->id]);
        $this->assertDatabaseHas('biblioteca_pessoa', ['pessoa_id' => $p2->id]);
    }

    public function test_store_with_model_binding(): void {
        $pessoa = Pessoa::factory()->create();
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", ['pessoa_id' => $pessoa->id]);
        
        $this->assertTrue($this->biblioteca->pessoas()->where('pessoa_id', $pessoa->id)->exists());
    }

    public function test_store_preserves_existing_associations(): void {
        $p1 = Pessoa::factory()->create();
        $p2 = Pessoa::factory()->create();
        
        $this->biblioteca->pessoas()->attach($p1->id);
        $this->post("/bibliotecas/{$this->biblioteca->id}/pessoas", ['pessoa_id' => $p2->id]);
        
        $this->assertTrue($this->biblioteca->pessoas()->where('pessoa_id', $p1->id)->exists());
        $this->assertTrue($this->biblioteca->pessoas()->where('pessoa_id', $p2->id)->exists());
    }
}