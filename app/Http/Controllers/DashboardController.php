<?php

namespace App\Http\Controllers;

use App\Models\Dept;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $members = User::with('department')->orderByDesc('id')->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_level' => $user->role_level,
            'is_msu_member' => $user->is_msu_member,
            'department_id' => $user->department_id,
            'department_name' => $user->department?->name,
            'status' => $user->status,
        ]);

        return Inertia::render('Dashboard', [
            'members' => $members,
            'departments' => Dept::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
