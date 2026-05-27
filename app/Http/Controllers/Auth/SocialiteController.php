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

        // ตรวจสอบในฐานข้อมูล: ถ้ามีอยู่แล้วให้ Update ถ้าไม่มีให้ Create
        $user = User::updateOrCreate([
            'email' => $googleUser->getEmail(),
        ], [
            'name' => $googleUser->getName(),
            'provider' => 'google',
            'provider_id' => $googleUser->getId(),
            // role_level จะถูกตั้งเป็น 1 โดย default จากฐานข้อมูลที่เราตั้งไว้
            // และจะไม่ทับค่าเดิมหากผู้ใช้คนนั้นมีระดับสูงกว่า 1 อยู่แล้ว
        ]);

        // สั่งให้ระบบทำการ Login
        Auth::login($user);

        // ส่งไปยังหน้า Dashboard หลังจาก Login สำเร็จ
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
