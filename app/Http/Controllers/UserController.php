<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function loggedInUser(Request $request)
    {
        try {
            $user = User::firstWhere('id', auth()->user()->id);

            $user->roles = $user->getRoleNames();
            $user->permissions = $user->getPermissionNames();

            return $user;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function assignAdminRole()
    {
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'edit posts']);

        $user = User::find(1);
        $roleName = 'admin';
        $user->assignRole($roleName);
        if ($roleName === 'admin') {
            $user->givePermissionTo(Permission::all());
        }

        return response()->json(['message' => 'Success'], 200);
    }
}
