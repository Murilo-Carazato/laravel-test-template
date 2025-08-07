<?php

namespace App\Domains\Core\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envia notificação push para usuários específicos
     *
     * @param array $userIds IDs dos usuários
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): bool
    {
        $tokens = User::whereIn('id', $userIds)
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->toArray();
            
        if (empty($tokens)) {
            return false;
        }
        
        return $this->sendToTokens($tokens, $title, $body, $data);
    }
    
    /**
     * Envia notificação para tokens específicos
     *
     * @param array $tokens Tokens de dispositivo
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        try {
            // Aqui seria implementada a integração com FCM ou outro serviço
            
            // Log para simulação
            Log::info('Enviando notificação para tokens', [
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
                'data' => $data
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação para tokens: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia notificação para um tópico
     *
     * @param string $topic Tópico
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            // Aqui seria implementada a integração com FCM ou outro serviço
            
            // Log para simulação
            Log::info('Enviando notificação para tópico', [
                'topic' => $topic,
                'title' => $title,
                'body' => $body,
                'data' => $data
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação para tópico: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra token de dispositivo para um usuário
     *
     * @param User $user Usuário
     * @param string $token Token do dispositivo
     * @param string $deviceType Tipo de dispositivo (android, ios, web)
     * @return bool
     */
    public function registerDeviceToken(User $user, string $token, string $deviceType): bool
    {
        try {
            $user->device_token = $token;
            $user->device_type = $deviceType;
            $user->save();
            
            Log::info('Token de dispositivo registrado', [
                'user_id' => $user->id,
                'device_type' => $deviceType,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao registrar token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inscreve um token em um tópico
     *
     * @param string $token Token do dispositivo
     * @param string $topic Tópico
     * @return bool
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            // Aqui seria implementada a inscrição no tópico via FCM
            
            // Log para simulação
            Log::info('Inscrevendo token em tópico', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'topic' => $topic
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao inscrever em tópico: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancela a inscrição de um token em um tópico
     *
     * @param string $token Token do dispositivo
     * @param string $topic Tópico
     * @return bool
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        try {
            // Aqui seria implementada a desinscrição do tópico via FCM
            
            // Log para simulação
            Log::info('Cancelando inscrição de token em tópico', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'topic' => $topic
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar inscrição em tópico: ' . $e->getMessage());
            return false;
        }
    }
}
