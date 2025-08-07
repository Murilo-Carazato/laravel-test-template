<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\User;
use App\Models\Profile;
use App\Models\Feature;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run()
    {
        // Obter usuários existentes para associar com os logs de auditoria
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('Criando novos usuários para Audit...');
            $users = User::factory()->count(5)->create();
        }
        
        // Definir possíveis ações de auditoria
        $actions = [
            'login', 
            'logout', 
            'create', 
            'update', 
            'delete', 
            'view', 
            'export', 
            'password_change',
            'permission_change'
        ];
        
        // Modelos para registros polimórficos
        $modelMapping = [
            User::class => $users->pluck('id')->toArray(),
            Profile::class => Profile::all()->pluck('id')->toArray() ?: [1, 2, 3],
            Feature::class => Feature::all()->pluck('id')->toArray() ?: [1, 2, 3, 4, 5]
        ];
        
        // Criar registros de auditoria para cada usuário
        foreach ($users as $user) {
            // Criar 5-15 registros de auditoria por usuário
            $recordCount = rand(5, 15);
            
            for ($i = 0; $i < $recordCount; $i++) {
                // Selecionar aleatoriamente um modelo e ID para relacionamento polimórfico
                $modelType = array_rand($modelMapping);
                $possibleIds = $modelMapping[$modelType];
                $modelId = $possibleIds[array_rand($possibleIds)];
                
                // Gerar dados de auditoria com base na ação
                $action = $actions[array_rand($actions)];
                $entity = $this->getEntityFromModelType($modelType);
                
                // Dados específicos baseados na ação
                $data = $this->generateDataForAction($action, $entity);
                
                // Criar registro de auditoria
                Audit::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'entity' => $entity,
                    'data' => $data,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'ip_address' => fake()->ipv4,
                    'user_agent' => fake()->userAgent,
                    'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Também adicionar alguns registros de auditoria para usuário nulo (sistema)
        for ($i = 0; $i < 10; $i++) {
            $modelType = array_rand($modelMapping);
            $possibleIds = $modelMapping[$modelType];
            $modelId = $possibleIds[array_rand($possibleIds)];
            
            $action = $actions[array_rand($actions)];
            $entity = $this->getEntityFromModelType($modelType);
            
            Audit::create([
                'user_id' => null, // Ações do sistema
                'action' => $action,
                'entity' => $entity,
                'data' => $this->generateDataForAction($action, $entity, true),
                'model_type' => $modelType,
                'model_id' => $modelId,
                'ip_address' => null,
                'user_agent' => 'System/Scheduler',
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                'updated_at' => now(),
            ]);
        }
        
        $this->command->info('Registros de auditoria criados com sucesso!');
    }
    
    /**
     * Retorna o tipo de entidade com base no tipo de modelo
     */
    private function getEntityFromModelType($modelType)
    {
        $mapping = [
            User::class => 'user',
            Profile::class => 'profile',
            Feature::class => 'feature'
        ];
        
        return $mapping[$modelType] ?? 'unknown';
    }
    
    /**
     * Gera dados específicos com base na ação e entidade
     */
    private function generateDataForAction($action, $entity, $isSystem = false)
    {
        $data = [
            'timestamp' => now()->timestamp,
            'details' => fake()->sentence(),
        ];
        
        if ($action === 'create' || $action === 'update') {
            if ($entity === 'user') {
                $data['fields'] = ['name', 'email'];
                $data['changes'] = [
                    'name' => [
                        'old' => fake()->name,
                        'new' => fake()->name,
                    ],
                    'email' => [
                        'old' => fake()->email,
                        'new' => fake()->email,
                    ]
                ];
            } elseif ($entity === 'profile') {
                $data['fields'] = ['bio', 'phone'];
                $data['changes'] = [
                    'bio' => [
                        'old' => fake()->paragraph(1),
                        'new' => fake()->paragraph(1),
                    ],
                    'phone' => [
                        'old' => fake()->phoneNumber,
                        'new' => fake()->phoneNumber,
                    ]
                ];
            } elseif ($entity === 'feature') {
                $data['fields'] = ['enabled', 'description'];
                $data['changes'] = [
                    'enabled' => [
                        'old' => false,
                        'new' => true,
                    ],
                    'description' => [
                        'old' => fake()->sentence,
                        'new' => fake()->sentence,
                    ]
                ];
            }
        } elseif ($action === 'login' || $action === 'logout') {
            $data['ip_address'] = fake()->ipv4;
            $data['browser'] = fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']);
            $data['os'] = fake()->randomElement(['Windows', 'MacOS', 'Linux', 'iOS', 'Android']);
            $data['success'] = fake()->boolean(90);
            
            if (!$data['success']) {
                $data['failure_reason'] = fake()->randomElement([
                    'Invalid credentials',
                    'Account locked',
                    'Too many attempts',
                    'Suspicious location'
                ]);
            }
        }
        
        if ($isSystem) {
            $data['automated'] = true;
            $data['source'] = fake()->randomElement(['scheduler', 'cron', 'system', 'maintenance']);
        }
        
        return $data;
    }
}