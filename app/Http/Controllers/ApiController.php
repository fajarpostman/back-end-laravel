<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    public function register(Request $request)
    {
        //validasi data
        $data = $request->only('name', 'email', 'password');
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|max:100'
        ]);

        //kirim reponse gagal jika request tidak valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //jika request valid, maka buat user baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        //user berhasil dibuat, maka return success
        return response()->json([
            'success' => true,
            'message' => 'User Created Successfully',
            'data' => $user
        ], Reponse::HTTP_OK);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credntials
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:100'
        ]);

        //Send failed maka reponse request tidak valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //jika Request valid
        //Crean token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'Message' => 'Login Credentials are Invalid.',
                ], 400);
            }
        } catch (JWTException $e) {
            return $credentials;
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
        }

        // Token created, return with success reponse and JWT token

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        // valid credentials
        $validator = Validator::make($request->only('token'), [
            'token' => 'required'
        ]);

        // Kirim response gagal jika tidak valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        // Request valid, lakukan logout
        try {
            JWTAuth::invalidate($request->token);
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_user(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }
}