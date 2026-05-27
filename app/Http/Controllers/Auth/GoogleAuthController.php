<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // ส่งผู้ใช้ไปที่หน้า Login ของ Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // รับข้อมูลกลับจาก Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // 🔍 ตรวจสอบโดเมน (Security Filter)
            if (!str_ends_with($googleUser->email, '@msu.ac.th')) {
                return redirect('/')->with('error', 'อนุญาตเฉพาะอีเมล @msu.ac.th เท่านั้น');
            }

            // 🔍 ค้นหา User ในระบบ (ที่เรา Seed ไว้)
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // ถ้าเจอ ให้ Login เลย
                Auth::login($user);
                
                // แยกทางไปตาม Role
                return $user->role_level >= 2 
                    ? redirect()->intended('/dashboard') 
                    : redirect('/');
            } else {
                return redirect('/')->with('error', 'ไม่พบข้อมูลผู้ใช้ในระบบ กรุณาติดต่อเจ้าหน้าที่');
            }

        } catch (\Exception $e) {
            return redirect('/')->with('error', 'การเชื่อมต่อกับ Google ขัดข้อง');
        }
    }
}
