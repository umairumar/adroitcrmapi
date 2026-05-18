<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use BelongsToTenant;

    protected $table = 'user';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'is_platform_admin',
        'email',
        'password',
        'utype',
        'name',
        'company',
        'agent_directline',
        'mon_fri',
        'att_sat',
        'att_sun',
        'status',
        'cby',
        'cdate',
        'mby',
        'mdate',
        'dateofjoining',
        'phoneno',
        'reference_name1',
        'reference_name2',
        'reference_phone1',
        'reference_phone2',
        'user_address',
        'image',
    ];

    protected $hidden = ['password'];

    protected $appends = ['companies'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'user_id', 'role_id')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function isPlatformAdmin(): bool
    {
        if ((bool) ($this->is_platform_admin ?? false)) {
            return true;
        }

        return ($this->utype ?? '') === 'sadmin';
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isPlatformAdmin()) {
            return true;
        }

        return DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->join('role_permission', 'role_permission.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $this->id)
            ->where('permissions.slug', $slug)
            ->exists();
    }

    public function getCompaniesAttribute()
    {
        if (empty($this->company)) {
            return collect();
        }

        $companyIds = array_filter(explode('-', trim($this->company, '-')), function ($v) {
            return is_numeric($v) && (int) $v > 0;
        });

        if (empty($companyIds)) {
            return collect();
        }

        $companyIds = array_map('intval', $companyIds);

        $query = DB::table('crm_company')->whereIn('id', $companyIds);

        if ($this->tenant_id) {
            $query->where('tenant_id', $this->tenant_id);
        }

        return $query
            ->orderByRaw('FIELD(id,' . implode(',', $companyIds) . ')')
            ->get();
    }
}
