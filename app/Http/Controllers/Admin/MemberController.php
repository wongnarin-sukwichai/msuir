<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class MemberController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $isMsuMember = $request->boolean('is_msu_member');

        $validated = $request->validate([
            'is_msu_member' => 'required|boolean',
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255', 'unique:users,email',
                Rule::when($isMsuMember, ['ends_with:@msu.ac.th']),
            ],
            'password' => [$isMsuMember ? 'nullable' : 'required', Rules\Password::defaults()],
            'role_level' => 'required|in:1,3',
            'department_id' => 'nullable|exists:deps,id',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $isMsuMember ? null : Hash::make($validated['password']),
            'role_level' => $validated['role_level'],
            'is_msu_member' => $isMsuMember,
            'department_id' => $validated['department_id'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'เพิ่มสมาชิกใหม่สำเร็จ');
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $isMsuMember = $request->boolean('is_msu_member');

        $validated = $request->validate([
            'is_msu_member' => 'required|boolean',
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member->id),
                Rule::when($isMsuMember, ['ends_with:@msu.ac.th']),
            ],
            'password' => ['nullable', Rule::when($request->filled('password'), [Rules\Password::defaults()])],
            'role_level' => 'required|in:1,3',
            'department_id' => 'nullable|exists:deps,id',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_level' => $validated['role_level'],
            'is_msu_member' => $isMsuMember,
            'department_id' => $validated['department_id'] ?? null,
        ];

        if ($isMsuMember) {
            $updateData['password'] = null;
        } elseif (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $member->update($updateData);

        return back()->with('success', 'บันทึกข้อมูลสมาชิกสำเร็จ');
    }

    public function toggleStatus(User $member): RedirectResponse
    {
        $member->update([
            'status' => $member->status === 'active' ? 'suspended' : 'active',
        ]);

        return back()->with('success', 'เปลี่ยนสถานะบัญชีสำเร็จ');
    }

    public function destroy(User $member): RedirectResponse
    {
        abort_if($member->id === Auth::id(), 403, 'ไม่สามารถลบบัญชีตนเองได้');

        $member->delete();

        return back()->with('success', 'ลบสมาชิกสำเร็จ');
    }

    public function impersonate(User $member): RedirectResponse
    {
        abort_if($member->id === Auth::id(), 403, 'ไม่สามารถสวมสิทธิ์บัญชีตนเองได้');
        abort_if($member->role_level == 3, 403, 'ไม่สามารถสวมสิทธิ์ผู้ดูแลระบบคนอื่นได้');
        abort_if($member->status !== 'active', 403, 'ไม่สามารถสวมสิทธิ์บัญชีที่ถูกระงับการใช้งาน');

        session(['impersonator_id' => Auth::id()]);
        Auth::login($member);

        return redirect('/')->with('success', 'กำลังสวมสิทธิ์เป็น '.$member->name);
    }
}
