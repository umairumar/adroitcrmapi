<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\BranchAccess;
use PHPUnit\Framework\TestCase;

class BranchAccessTest extends TestCase
{
    public function test_legacy_company_ids_parsing(): void
    {
        $user = new User(['company' => '-2-4-']);

        $access = new BranchAccess;
        $ids = $access->legacyCompanyIdsFromUser($user);

        $this->assertSame([2, 4], $ids);
    }

    public function test_empty_company_returns_empty_array(): void
    {
        $user = new User(['company' => '']);

        $access = new BranchAccess;
        $this->assertSame([], $access->legacyCompanyIdsFromUser($user));
    }
}
