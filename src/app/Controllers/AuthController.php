<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Support\HttpStatus;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth = new AuthService())
    {
    }

    /** @param array<string, string> $params */
    public function register(Request $request, array $params): Response
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:255',
        ]);

        return $this->created($this->auth->register([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => (string) $data['password'],
        ]));
    }

    /** @param array<string, string> $params */
    public function login(Request $request, array $params): Response
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        return $this->ok($this->auth->login([
            'email' => (string) $data['email'],
            'password' => (string) $data['password'],
        ]));
    }

    /** @param array<string, string> $params */
    public function me(Request $request, array $params): Response
    {
        $user = $request->user();

        if ($user === null) {
            $this->abort(HttpStatus::UNAUTHORIZED, 'Usuário autenticado não encontrado.');
        }

        return $this->ok(['user' => $user]);
    }

    /** @param array<string, string> $params */
    public function logout(Request $request, array $params): Response
    {
        Auth::logout($request);

        return $this->noContent();
    }
}
