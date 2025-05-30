<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate; // Descomente se e quando for usar Gates
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy', // Exemplo de como registrar políticas
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Aqui você pode definir Gates ou outras lógicas de autorização
    }
}