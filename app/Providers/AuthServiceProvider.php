<?php

        namespace App\Providers;

        use App\Models\User; // Ajuste o namespace do seu modelo User se necessário
        use App\Models\Profile; // Exemplo, se você tiver um modelo Profile
        use App\Policies\UserPolicy; // Será criado abaixo
        use App\Policies\ProfilePolicy; // Exemplo
        use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
        // use Illuminate\Support\Facades\Gate; // Descomente se for usar Gates diretamente

        class AuthServiceProvider extends ServiceProvider
        {
            /**
             * The policy mappings for the application.
             *
             * @var array<class-string, class-string>
             */
            protected $policies = [
                User::class => UserPolicy::class,
                // Profile::class => ProfilePolicy::class, // Exemplo para um modelo Profile
            ];

            /**
             * Register any authentication / authorization services.
             */
            public function boot(): void
            {
                $this->registerPolicies();

                // Aqui você pode definir Gates se preferir para ações mais simples
                // Gate::define('update-post', function (User $user, Post $post) {
                //     return $user->id === $post->user_id;
                // });
            }
        }