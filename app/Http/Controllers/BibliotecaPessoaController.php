<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biblioteca;
use App\Models\Pessoa;

class BibliotecaPessoaController extends Controller
{
    public function create($id)
    {
        // 1. Busca manual da Biblioteca para evitar falhas do Route Model Binding
        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            abort(404);
        }

        $pessoas = Pessoa::whereDoesntHave('bibliotecas', function ($query) use ($biblioteca) {
            $query->where('biblioteca_id', $biblioteca->id);
        })->get();

        return view('bibliotecas.add_pessoa', compact('biblioteca', 'pessoas'));
    }

    public function store(Request $request, $id)
    {
        // 1. Busca manual da Biblioteca
        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            abort(404);
        }

        // 2. Validação da pessoa enviada
        $request->validate([
            'pessoa_id' => 'required|exists:pessoas,id',
        ]);

        $pessoaId = $request->input('pessoa_id');

        // 3. Tenta sincronizar
        $result = $biblioteca->pessoas()->syncWithoutDetaching([$pessoaId]);

        // 4. Redirecionamento de Sucesso ou Erro
        if (empty($result['attached'])) {
            return redirect("/bibliotecas/{$biblioteca->id}/pessoas/add")
                ->with('error', 'Pessoa já está associada a esta biblioteca.');
        }

        return redirect("/bibliotecas/edit/{$biblioteca->id}")
            ->with('message', 'Pessoa adicionada à biblioteca com sucesso.');
    }
}