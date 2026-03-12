<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'description',
        'is_active',
        'level', // 1-100, higher = more privileges
        'permissions_count',
        'users_count',
        'color',
        'icon',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
        'permissions_count' => 'integer',
        'users_count' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeByLevel($query, $minLevel = null, $maxLevel = null)
    {
        if ($minLevel !== null) {
            $query->where('level', '>=', $minLevel);
        }
        if ($maxLevel !== null) {
            $query->where('level', '<=', $maxLevel);
        }
        return $query;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('level')->orderBy('name');
    }

    // Methods
    public static function createRole($name, $description = null, $level = 50)
    {
        $slug = static::generateSlug($name);
        
        return static::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'level' => $level,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public static function generateSlug($name)
    {
        return strtolower(str_replace(' ', '_', $name));
    }

    public function givePermission($permissionId)
    {
        $this->permissions()->syncWithoutDetaching([$permissionId]);
        $this->refreshPermissionsCount();
        return $this;
    }

    public function givePermissions(array $permissionIds)
    {
        $this->permissions()->syncWithoutDetaching($permissionIds);
        $this->refreshPermissionsCount();
        return $this;
    }

    public function revokePermission($permissionId)
    {
        $this->permissions()->detach($permissionId);
        $this->refreshPermissionsCount();
        return $this;
    }

    public function revokePermissions(array $permissionIds)
    {
        $this->permissions()->detach($permissionIds);
        $this->refreshPermissionsCount();
        return $this;
    }

    public function syncPermissions(array $permissionIds)
    {
        $this->permissions()->sync($permissionIds);
        $this->refreshPermissionsCount();
        return $this;
    }

    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('permissions.slug', $permission)->exists();
        }
        
        return $this->permissions()->where('permissions.id', $permission->id)->exists();
    }

    public function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    public function getPermissionsByGroup()
    {
        return $this->permissions()
            ->with('roles')
            ->get()
            ->groupBy('group');
    }

    public function getPermissionSlugs()
    {
        return $this->permissions()->pluck('slug')->toArray();
    }

    public function refreshPermissionsCount()
    {
        $this->update(['permissions_count' => $this->permissions()->count()]);
    }

    public function refreshUsersCount()
    {
        $this->update(['users_count' => $this->users()->count()]);
    }

    public function canBeDeleted()
    {
        return !$this->is_system && $this->users()->count() === 0;
    }

    public function assignToUser($userId)
    {
        $this->users()->syncWithoutDetaching([$userId]);
        $this->refreshUsersCount();
        return $this;
    }

    public function removeFromUser($userId)
    {
        $this->users()->detach($userId);
        $this->refreshUsersCount();
        return $this;
    }

    public function getLevelText()
    {
        return match (true) {
            $this->level >= 90 => 'Super Admin',
            $this->level >= 70 => 'Admin',
            $this->level >= 50 => 'Manager',
            $this->level >= 30 => 'Supervisor',
            $this->level >= 10 => 'Staff',
            default => 'User',
        };
    }

    public function getLevelColor()
    {
        return match (true) {
            $this->level >= 90 => 'red',
            $this->level >= 70 => 'orange',
            $this->level >= 50 => 'yellow',
            $this->level >= 30 => 'blue',
            $this->level >= 10 => 'green',
            default => 'gray',
        };
    }

    // Static methods
    public static function getAllSystemRoles()
    {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'supervisor' => 'Supervisor',
            'staff' => 'Staff',
            'instructor' => 'Instructor',
            'student' => 'Student',
            'vendor' => 'Vendor',
        ];
    }

    public static function bootSystemRoles()
    {
        $systemRoles = static::getAllSystemRoles();
        
        foreach ($systemRoles as $slug => $name) {
            static::firstOrCreate([
                'slug' => $slug,
                'is_system' => true,
            ], [
                'name' => $name,
                'display_name' => $name,
                'description' => "System role: {$name}",
                'level' => static::getSystemRoleLevel($slug),
                'is_active' => true,
            ]);
        }
    }

    private static function getSystemRoleLevel($slug)
    {
        return match ($slug) {
            'super_admin' => 100,
            'admin' => 90,
            'manager' => 70,
            'supervisor' => 50,
            'staff' => 30,
            'instructor' => 40,
            'student' => 10,
            'vendor' => 35,
            default => 10,
        };
    }
}
