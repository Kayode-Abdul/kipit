<?php

namespace App;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class MyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        // No URL prefix needed as files are stored globally in public/storage
    }

    public function revert()
    {
    }
}
