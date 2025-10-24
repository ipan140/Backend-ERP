<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 🆕 REGISTER
     */
    public function register(Request $request)
    {
        // Validasi ringkas + tegas
        $data = $request->validate([
            'name'                  => ['required','string','max:255'],
            'email'                 => ['required','string','email:rfc,dns','max:255','unique:users,email'],
            'password'              => ['required','string','min:8','max:72','confirmed'],
        ]);

        // Normalisasi email (case-insensitive)
        $data['email'] = mb_strtolower(trim($data['email']));

        try {
            // Buat user
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Buat token untuk device ini
            $deviceName = $request->input('device_name', 'api');
            $token = $user->createToken($deviceName)->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Registrasi berhasil',
                'token'   => $token,
                'token_type' => 'Bearer',
                'user'    => $user,
            ], 201);

        } catch (\Throwable $e) {
            // Antisipasi race condition unique email, dll.
            return response()->json([
                'status'  => false,
                'message' => 'Registrasi gagal',
                'errors'  => ['server' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * 🔑 LOGIN
     * - default: multi-device (token baru TANPA menghapus token lama)
     * - jika ingin single device, kirimkan single_device=true untuk wipe token lama
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'         => ['required','string','email:rfc,dns','max:255'],
            'password'      => ['required','string'],
            'device_name'   => ['sometimes','string','max:100'],
            'single_device' => ['sometimes','boolean'], // true = hapus semua token lama
        ]);

        $email = mb_strtolower(trim($data['email']));
        $user  = User::where('email', $email)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Jangan bocorkan mana yang salah
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        // Opsi single device
        if ($request->boolean('single_device')) {
            $user->tokens()->delete();
        }

        $deviceName = $request->input('device_name', 'api');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status'     => true,
            'message'    => 'Login sukses',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user,
        ], 200);
    }

    /**
     * 🔒 LOGOUT
     * - default: logout current token saja
     * - jika ?all=true maka hapus semua token user
     */
    public function logout(Request $request)
    {
        if ($request->boolean('all')) {
            $request->user()->tokens()->delete();
        } else {
            // Hapus token yang dipakai saat ini saja
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil',
        ], 200);
    }

    /**
     * 👤 PROFILE USER (protected)
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'user'   => $request->user(),
        ], 200);
    }
}
