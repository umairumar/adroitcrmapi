<?php
namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use App\Mail\MEmail;
use Carbon\Carbon;
use App\Models\User;

class AuthController extends Controller
{
    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::withoutGlobalScopes()->where('email', $request->email)->first();

        //dd (Hash::check($request->password, $user->password));
        //exit;
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('crm-token')->plainTextToken;

        $tenant = $user->tenant_id
            ? \App\Models\Tenant::find($user->tenant_id)
            : null;

        return response()->json([
            'status' => true,
            'token'  => $token,
            'user'   => $user,
            'tenant' => $tenant,
            'is_platform_admin' => $user->isPlatformAdmin(),
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully'
        ]);
    }

    // FORGOT PASSWORD
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status'=>false,'message'=>'Email not found'],404);
        }

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        /*Mail::raw("Your reset token: $token", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject("CRM Password Reset");
        });*/

        $message = "Your reset token: $token";
        
        //dd ($message);
        //exit;
        try {
            Mail::to($request->email)->send(new MEmail($message, "CRM Password Reset"));
        
            return response()->json([
                'status'  => true,
                'token'   => $token,
                'message' => 'Email sent successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Email failed to send',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    // RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>false,'errors'=>$validator->errors()],422);
        }

        $row = DB::table('password_resets')
            ->where('email',$request->email)
            ->where('token',$request->token)
            ->first();

        if (!$row) {
            return response()->json(['status'=>false,'message'=>'Invalid Information Given'],400);
        }

        User::where('email',$request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_resets')->where('email',$request->email)->delete();

        return response()->json([
            'status'=>true,
            'message'=>'Password reset successful'
        ]);
    }

}