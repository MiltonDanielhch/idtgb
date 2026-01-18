<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Person;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Policies\PersonPolicy;
use App\Policies\ParentescoPolicy;
use App\Policies\TipoTransmisionPolicy;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Person::class => PersonPolicy::class,
        Parentesco::class => ParentescoPolicy::class,
        TipoTransmision::class => TipoTransmisionPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
