<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Support\Facades\DB;
class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = false;


    protected $fillable = [
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

    // Hide any real 'companies' relationship if exists
    protected $appends = ['companies']; // make it part of JSON automatically

    // Custom accessor to return multiple companies
    public function getCompaniesAttribute()
    {
        if (empty($this->company)) return collect();

        // Remove leading/trailing dashes and explode
        $companyIds = array_filter(explode('-', trim($this->company, '-')), function($v) {
            return is_numeric($v) && (int)$v > 0;
        });

        if (empty($companyIds)) return collect();

        $companyIds = array_map('intval', $companyIds);

        // Fetch companies in the order of IDs in the string
        $companies = DB::table('crm_company')
            ->whereIn('id', $companyIds)
            ->orderByRaw("FIELD(id," . implode(',', $companyIds) . ")")
            ->get();

        return $companies;
    }
}