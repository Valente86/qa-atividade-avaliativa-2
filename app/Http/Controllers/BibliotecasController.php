<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Biblioteca;

class BibliotecasController extends Controller
{
    public function index(Request $request)
    {
        $busca = $request->input('nome');
        $bibliotecas = $busca ? Biblioteca::where('nome', 'like', "%$busca%")->get() : Biblioteca::all();
        return view('bibliotecas.index', compact('bibliotecas'));
    }

    public function create()
    {
        $users = \App\Models\User::all();
        return view('bibliotecas.new', compact('users'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('nome')) {
            return redirect('/bibliotecas/new')->with('error', 'Nome é obrigatório');
        }

        try {
            $biblioteca = Biblioteca::create([
                'created_by' => $request->input("created_by") ?? auth()->id() ?? 1,
                'nome' => $request->nome
            ]);
            $biblioteca->endereco = $request->endereco;
            $biblioteca->save();
        } catch (\Throwable $e) {
            return redirect('/bibliotecas/new')->with('error', 'Erro ao criar');
        }

        return redirect('/bibliotecas')->with('message', 'Criada com sucesso');
    }

    public function edit(int $id)
    {
        $users = \App\Models\User::all();
        $biblioteca = Biblioteca::find($id);
        
        if (!$biblioteca) {
            return redirect('/bibliotecas')->with('error', 'Não encontrada');
        }
        
        return view('bibliotecas.edit', compact('biblioteca', 'users'));
    }

    public function update(Request $request, int $id)
    {
        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            return response()->json(['error' => 'Não encontrada'], 404);
        }

        try {
            if ($request->filled('created_by')) $biblioteca->created_by = $request->created_by;
            if ($request->filled('nome')) $biblioteca->nome = $request->nome;
            if ($request->filled('endereco')) $biblioteca->endereco = $request->endereco;
            if ($request->filled('email')) $biblioteca->email = $request->email;
            
            $biblioteca->save();
        } catch (\Throwable $e) {
            return redirect('/bibliotecas/new')->with('error', 'Erro ao atualizar');
        }

        return redirect('/bibliotecas')->with('message', 'Atualizada com sucesso');
    }

    public function destroy(int $id)
    {
        $biblioteca = Biblioteca::find($id);
        if (!$biblioteca) {
            return response()->json(['error' => 'Não encontrada'], 404);
        }

        try {
            $biblioteca->delete();
        } catch (\Throwable $e) {
            return redirect('/bibliotecas')->with('message', 'Erro ao excluir');
        }

        return redirect('/bibliotecas')->with('message', 'Excluída com sucesso');
    }
}