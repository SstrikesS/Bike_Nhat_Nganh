<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255|min:3',
            'email'    => 'required|email|unique:users|max:255',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'error' => [
                    'warning' => $errors
                ],
                'code'  => 400
            ], 400);

        } else if ($validator->passes()) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::table('users')
                ->where('users.email', 'LIKE', $request->email)
                ->update([
                    'classifical' => 4,
                    'local'       => $request->post('local'),
                    'api_token' => $token
                ]);

            return response()->json([
                'api_token' => $token,
                'token_type'   => 'Bearer',
                'success'      => 'true',
                'code'         => 200
            ]);
        }

        return response()->json([
            'error' => [
                'warning' => 'Server khong phan hoi!'
            ],
            'code'  => 500
        ], 500);
    }

    public function login(Request $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
                'error' => [
                    'message' => 'Email hoac mat khau khong dung'
                ],
                'code'  => 401
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        if($request->is('api/*')){
            $role = Role::findById(($user->toArray())['classifical'] + 2);
        }else{
            $role = Role::findById(($user->toArray())['classifical']);
        }


        $user->assignRole($role);


        $token = $user->createToken('auth_token')->plainTextToken;

        DB::table('users')
            ->where('users.email', 'LIKE', $request->email)
            ->update([
                'api_token' => $token
            ]);

        $data['data'] = $user->toArray();
        $data['data']['api_token'] = $token;
        $data['data']['token_type'] = 'Bearer';
        $data['data']['success'] = 'true';
        $data['data']['code'] = 200;

        return response()->json($data);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

}
