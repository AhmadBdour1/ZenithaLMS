<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'description',
        'group', // 'users', 'courses', 'subscriptions', 'marketplace', 'pagebuilder', 'certificates', 'stuff', 'admin', 'system'
        'type', // 'create', 'read', 'update', 'delete', 'manage', 'admin'
        'entity', // 'user', 'course', 'subscription', 'aura_product', 'aura_order', 'aura_page', 'certificate', 'stuff', 'admin_setting'
        'action', // 'create', 'view', 'edit', 'delete', 'publish', 'archive', 'manage', 'configure'
        'is_system',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions');
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

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByEntity($query, $entity)
    {
        return $query->where('entity', $entity);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('group')->orderBy('sort_order')->orderBy('name');
    }

    // Methods
    public static function createPermission($name, $group, $type, $entity, $action, $description = null)
    {
        $slug = static::generateSlug($name, $entity, $action);
        
        return static::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'group' => $group,
            'type' => $type,
            'entity' => $entity,
            'action' => $action,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public static function generateSlug($name, $entity, $action)
    {
        return strtolower($entity . '.' . $action);
    }

    public function getDisplayName()
    {
        return $this->name;
    }

    public function getGroupDisplayName()
    {
        return match ($this->group) {
            'users' => 'Users Management',
            'courses' => 'Courses Management',
            'subscriptions' => 'Subscriptions Management',
            'marketplace' => 'Marketplace Management',
            'pagebuilder' => 'Page Builder Management',
            'certificates' => 'Certificates Management',
            'stuff' => 'Stuff Management',
            'admin' => 'Admin Management',
            'system' => 'System Management',
            default => ucfirst($this->group),
        };
    }

    public function getTypeDisplayName()
    {
        return match ($this->type) {
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
            'manage' => 'Manage',
            'admin' => 'Admin',
            default => ucfirst($this->type),
        };
    }

    public function getEntityDisplayName()
    {
        return match ($this->entity) {
            'user' => 'User',
            'course' => 'Course',
            'subscription' => 'Subscription',
            'aura_product' => 'Aura Product',
            'aura_order' => 'Aura Order',
            'aura_page' => 'Aura Page',
            'certificate' => 'Certificate',
            'stuff' => 'Stuff',
            'admin_setting' => 'Admin Setting',
            default => ucfirst($this->entity),
        };
    }

    public function getActionDisplayName()
    {
        return match ($this->action) {
            'create' => 'Create',
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'publish' => 'Publish',
            'archive' => 'Archive',
            'manage' => 'Manage',
            'configure' => 'Configure',
            default => ucfirst($this->action),
        };
    }

    public function canBeDeleted()
    {
        return !$this->is_system && $this->roles()->count() === 0 && $this->users()->count() === 0;
    }

    public function assignToRole($roleId)
    {
        $this->roles()->syncWithoutDetaching([$roleId]);
        return $this;
    }

    public function removeFromRole($roleId)
    {
        $this->roles()->detach($roleId);
        return $this;
    }

    public function assignToUser($userId)
    {
        $this->users()->syncWithoutDetaching([$userId]);
        return $this;
    }

    public function removeFromUser($userId)
    {
        $this->users()->detach($userId);
        return $this;
    }

    // Static methods for getting all permissions
    public static function getAllGroups()
    {
        return [
            'users' => 'Users Management',
            'courses' => 'Courses Management',
            'subscriptions' => 'Subscriptions Management',
            'marketplace' => 'Marketplace Management',
            'pagebuilder' => 'Page Builder Management',
            'certificates' => 'Certificates Management',
            'stuff' => 'Stuff Management',
            'admin' => 'Admin Management',
            'system' => 'System Management',
        ];
    }

    public static function getAllTypes()
    {
        return [
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
            'manage' => 'Manage',
            'admin' => 'Admin',
        ];
    }

    public static function getAllEntities()
    {
        return [
            'user' => 'User',
            'course' => 'Course',
            'subscription' => 'Subscription',
            'aura_product' => 'Aura Product',
            'aura_order' => 'Aura Order',
            'aura_page' => 'Aura Page',
            'certificate' => 'Certificate',
            'stuff' => 'Stuff',
            'admin_setting' => 'Admin Setting',
        ];
    }

    public static function getAllActions()
    {
        return [
            'create' => 'Create',
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'publish' => 'Publish',
            'archive' => 'Archive',
            'manage' => 'Manage',
            'configure' => 'Configure',
        ];
    }
}
