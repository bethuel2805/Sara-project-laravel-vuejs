<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a new user
     * Le premier compte créé sera automatiquement admin
     * Si un admin existe déjà, l'inscription est bloquée
     */
    public function register(Request $request)
    {
        // Vérifier s'il existe déjà un admin
        $adminExists = User::where('role', 'admin')->exists();

        if ($adminExists) {
            return response()->json([
                'message' => 'L\'inscription est désactivée. Veuillez contacter un administrateur pour créer un compte.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Champs manquants',
                'errors' => $validator->errors()
            ], 400);
        }

        // Le premier compte sera automatiquement admin
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // Premier compte = admin
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], 201);
    }

    /**
     * Vérifier si l'inscription est possible
     * Utile pour le frontend pour afficher/masquer le formulaire d'inscription
     */
    public function canRegister()
    {
        $adminExists = User::where('role', 'admin')->exists();
        
        return response()->json([
            'can_register' => !$adminExists,
            'message' => $adminExists 
                ? 'Un administrateur existe déjà. L\'inscription est désactivée.' 
                : 'L\'inscription est ouverte. Le premier compte sera administrateur.'
        ]);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email et mot de passe requis'
            ], 400);
        }

        $credentials = $request->only('email', 'password');
        
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $user = auth()->user();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Utilisateur introuvable'
                ], 401);
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token invalide'
            ], 401);
        }
    }
}
