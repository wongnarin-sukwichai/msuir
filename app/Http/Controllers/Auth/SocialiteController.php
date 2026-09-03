<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

use App\Models\User;

class SocialiteController extends Controller
{
    /**
     * ส่งผู้ใช้ไปยังหน้า Login ของ Google
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * รับข้อมูลหลังจากผู้ใช้ Login ผ่าน Google สำเร็จ
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            // หากเกิดข้อผิดพลาด ให้กลับไปหน้า login พร้อมแจ้งเตือน
            return redirect()->route('login')->with('status', 'ยืนยันตัวตนไม่สำเร็จ');
        }

        // ตรวจสอบว่าเป็นอีเมล @msu.ac.th หรือไม่
        if (!str_ends_with($googleUser->getEmail(), '@msu.ac.th')) {
            return redirect()->route('login')->with('status', 'ขออภัย! ระบบนี้อนุญาตเฉพาะบุคลากรที่ใช้ @msu.ac.th เท่านั้น');
        }

        // ถ้ามีอยู่แล้วให้ update ข้อมูลโปรไฟล์; ถ้าไม่มีให้สร้างใหม่
        // role_level ใช้ค่า default ของคอลัมน์ (= 1 สมาชิกทั่วไป) และจะไม่ทับของเดิมที่สูงกว่า
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'provider' => 'google',
                'provider_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ],
        )->refresh();

        Auth::login($user, remember: true);

        // staff/admin → หลังบ้าน; สมาชิกทั่วไป → หน้าแรก
        return (int) $user->role_level >= 2
            ? redirect()->intended(route('dashboard', absolute: false))
            : redirect()->intended('/')->with('status', 'เข้าสู่ระบบสำเร็จ');
    }
}
