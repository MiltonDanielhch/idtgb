<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Person;
use App\Models\Parentesco;
use App\Policies\PersonPolicy;
use App\Policies\ParentescoPolicy;
use Illuminate\Support\Facades\Log;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Person::class => PersonPolicy::class,
        Parentesco::class => ParentescoPolicy::class,
    ];

    public function boot()
    {
        Log::info('=== AUTH SERVICE PROVIDER BOOT ===');
        Log::info('Registered policies: ', array_keys($this->policies));

        $this->registerPolicies();

        Log::info('Policies registered successfully');
    }
}
