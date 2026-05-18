<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CrmLead;
use App\Services\Auth\AuthorizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ScopesLeadQueries
{
    protected function scopedLeadsQuery(Request $request): Builder
    {
        return app(AuthorizationService::class)
            ->scopeLeads(CrmLead::query(), $request->user());
    }
}
