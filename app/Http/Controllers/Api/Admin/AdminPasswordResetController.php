<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AdminPasswordResetController extends Controller
{
    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'email' => ['Admin with this email does not exist.'],
            ]);
        }

        // Delete any existing reset tokens for this admin
        DB::table('admin_password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Create new reset token
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes(60); // Token expires in 1 hour

        DB::table('admin_password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // In a real application, you would send an email here
        // For now, we'll just return the token (remove this in production)
        return response()->json([
            'message' => 'Password reset link sent to your email.',
            'token' => $token, // Remove this in production
        ]);
    }

    /**
     * Reset password
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetRecord = DB::table('admin_password_reset_tokens')
            ->where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired reset token.'],
            ]);
        }

        $admin = Admin::where('email', $request->email)->first();
        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the used reset token
        DB::table('admin_password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * Verify reset token
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $resetRecord = DB::table('admin_password_reset_tokens')
            ->where('email', $request->email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired token.',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Token is valid.',
        ]);
    }
}
