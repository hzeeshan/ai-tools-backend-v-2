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
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        if (!Permission::where('name', 'edit posts')->exists()) {
            Permission::create(['name' => 'edit posts']);
        }

        $email = "hafizzeeshan619@gmail.com";
        $user = User::where('email', $email)->first();
        $roleName = 'admin';

        // Assign the role to the user
        $user->assignRole($roleName);

        // Give the user permission to all permissions if the role is admin
        if ($roleName === 'admin') {
            $user->givePermissionTo(Permission::all());
        }

        return response()->json(['message' => 'Success'], 200);
    }
}
