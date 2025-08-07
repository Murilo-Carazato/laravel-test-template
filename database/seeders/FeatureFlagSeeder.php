<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureFlagSeeder extends Seeder
{
    public function run()
    {
        // ✅ ADICIONADO: Features específicas para teste do UserService
        $features = [
            [
                'name' => 'user_detailed_profile',
                'enabled' => true,
                'description' => 'Include roles and permissions in user profile response',
            ],
            [
                'name' => 'user_enhanced_update_response',
                'enabled' => true,
                'description' => 'Include additional data in user update response',
            ],
            [
                'name' => 'user_large_pagination',
                'enabled' => true,
                'description' => 'Allow larger pagination limits for users',
            ],
            [
                'name' => 'user_welcome_email',
                'enabled' => true,
                'description' => 'Send welcome email when user is created',
            ],
            [
                'name' => 'dark_mode',
                'enabled' => true,
                'description' => 'Ativa o tema escuro na interface',
            ],
            [
                'name' => 'advanced_reports',
                'enabled' => true,
                'description' => 'Habilita relatórios avançados',
            ],
            [
                'name' => 'beta_features',
                'enabled' => false,
                'description' => 'Recursos em fase de testes',
            ],
            [
                'name' => 'api_access',
                'enabled' => true,
                'description' => 'Permite acesso à API externa',
            ],
            [
                'name' => 'new_dashboard',
                'enabled' => false,
                'description' => 'Nova versão do dashboard',
            ]
        ];
        
        // Criar features definidas
        foreach ($features as $feature) {
            Feature::firstOrCreate(
                ['name' => $feature['name']],
                [
                    'enabled' => $feature['enabled'],
                    'description' => $feature['description'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
        
        // ✅ MELHORADO: Atribuir features de teste para usuários
        $users = User::take(5)->get();
        
        if ($users->count() > 0) {
            foreach ($users as $user) {
                // Ativar features específicas para teste
                $testFeatures = [
                    'user_detailed_profile' => true,
                    'user_enhanced_update_response' => false, // Para testar diferença
                    'user_large_pagination' => true,
                ];
                
                foreach ($testFeatures as $featureName => $enabled) {
                    DB::table('feature_user')->updateOrInsert(
                        [
                            'feature_name' => $featureName,
                            'user_id' => $user->id,
                        ],
                        [
                            'enabled' => $enabled,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }
        }
    }
}