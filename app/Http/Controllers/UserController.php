<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('/users')->with('error', 'Usuário não encontrado');
        }
        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.new');
    }

    public function store(Request $request)
    {
        // Validação manual para evitar erros 500 no banco
        if (!$request->filled('name') || !$request->filled('email') || !$request->filled('password')) {
            return redirect('/users/create')->with('error', 'Erro: Dados incompletos.');
        }

        $existing = User::where('email', $request->email)->first();
        if ($existing) {
            return redirect('/users/create')->with('error', 'Erro: Email duplicado.');
        }

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);
        } catch (\Throwable $e) {
            return redirect('/users/create')->with('error', 'Erro ao criar o usuário.');
        }

        return redirect('/users')->with('message', 'Usuário criado com sucesso');
    }

    public function edit($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('/users')->with('error', 'Usuário não encontrado');
        }
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('/users')->with('error', 'Usuário não encontrado');
        }

        if ($request->filled('email') && $request->email !== $user->email) {
            $existing = User::where('email', $request->email)->first();
            if ($existing) {
                return redirect("/users/{$id}/edit")->with('error', 'Email já está em uso.');
            }
        }

        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);
        
        if ($request->has('role')) {
            $user->role = $request->input('role');
        }

        try {
            $user->save();
        } catch (\Throwable $e) {
            return redirect("/users/{$id}/edit")->with('error', 'Erro ao atualizar.');
        }

        return redirect('/users')->with('message', 'Usuário atualizado com sucesso');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('/users')->with('error', 'Usuário não encontrado');
        }

        try {
            $user->delete();
        } catch (\Throwable $e) {
            return redirect('/users')->with('error', 'Erro ao excluir o usuário.');
        }

        return redirect('/users')->with('message', 'Usuário excluído com sucesso');
    }
}