<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::create(['name' => 'admin']);
        $teacher = Role::create(['name' => 'teacher']);
        $student = Role::create(['name' => 'student']);

        // Create permissions for subjects
        Permission::create(['name' => 'view_any_subject']);
        Permission::create(['name' => 'view_subject']);
        Permission::create(['name' => 'create_subject']);
        Permission::create(['name' => 'update_subject']);
        Permission::create(['name' => 'delete_subject']);

        // Create permissions for grades
        Permission::create(['name' => 'view_any_grade']);
        Permission::create(['name' => 'view_grade']);
        Permission::create(['name' => 'create_grade']);
        Permission::create(['name' => 'update_grade']);
        Permission::create(['name' => 'delete_grade']);

        // Create permissions for marks
        Permission::create(['name' => 'view_any_mark']);
        Permission::create(['name' => 'view_mark']);
        Permission::create(['name' => 'create_mark']);
        Permission::create(['name' => 'update_mark']);
        Permission::create(['name' => 'delete_mark']);

        // Create permissions for users
        Permission::create(['name' => 'view_any_user']);
        Permission::create(['name' => 'view_user']);
        Permission::create(['name' => 'create_user']);
        Permission::create(['name' => 'update_user']);
        Permission::create(['name' => 'delete_user']);

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());

        $teacher->givePermissionTo([
            'view_any_subject',
            'view_subject',
            'view_any_grade',
            'view_grade',
            'view_any_mark',
            'view_mark',
            'create_mark',
            'update_mark',
            'delete_mark',
        ]);

        $student->givePermissionTo([
            'view_any_mark',
            'view_mark',
        ]);

        // Create admin user
        $user = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('admin');
    }
} 