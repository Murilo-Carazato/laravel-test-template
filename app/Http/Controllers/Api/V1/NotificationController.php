<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class NotificationController extends ApiController
{
    /**
     * Envia uma notificação push para um usuário ou grupo de usuários
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'sometimes|array',
            'user_ids' => 'sometimes|array',
            'topic' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            // Verificar se estamos enviando para tokens específicos ou para um tópico
            if ($request->has('user_ids')) {
                // Buscar tokens dos usuários
                $tokens = User::whereIn('id', $request->user_ids)
                    ->whereNotNull('device_token')
                    ->pluck('device_token')
                    ->toArray();
                
                // Se não houver tokens, retornamos um erro
                if (empty($tokens)) {
                    return $this->errorResponse('Nenhum dispositivo encontrado para os usuários selecionados', 404);
                }

                // Enviar para tokens específicos
                $this->sendToTokens($request->title, $request->body, $tokens, $request->data ?? []);
            } else if ($request->has('topic')) {
                // Enviar para um tópico
                $this->sendToTopic($request->title, $request->body, $request->topic, $request->data ?? []);
            } else {
                return $this->errorResponse('É necessário informar user_ids ou topic', 422);
            }

            return $this->successResponse('Notificação enviada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao enviar notificação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Método para enviar notificação para tokens específicos
     * 
     * @param string $title
     * @param string $body
     * @param array $tokens
     * @param array $data
     * @return void
     */
    private function sendToTokens($title, $body, $tokens, $data = [])
    {
        // Aqui você implementaria o envio usando o sdk do Firebase ou via HTTP
        // Exemplo usando HTTP (você precisaria configurar credenciais Firebase)
        
        /*
        $response = Http::withHeaders([
            'Authorization' => 'key=' . config('services.firebase.server_key'),
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ]);
        */

        // Por enquanto, só logamos o que seria enviado
        \Log::info('Enviando notificação para tokens', [
            'tokens' => $tokens,
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);
    }

    /**
     * Método para enviar notificação para um tópico
     * 
     * @param string $title
     * @param string $body
     * @param string $topic
     * @param array $data
     * @return void
     */
    private function sendToTopic($title, $body, $topic, $data = [])
    {
        // Aqui você implementaria o envio usando o sdk do Firebase ou via HTTP
        // Exemplo usando HTTP (você precisaria configurar credenciais Firebase)
        
        /*
        $response = Http::withHeaders([
            'Authorization' => 'key=' . config('services.firebase.server_key'),
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data
        ]);
        */

        // Por enquanto, só logamos o que seria enviado
        \Log::info('Enviando notificação para tópico', [
            'topic' => $topic,
            'title' => $title,
            'body' => $body,
            'data' => $data
        ]);
    }

    /**
     * Registra o token de dispositivo do usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => [
                'required',
                'string',
                'max:255',
                // Regex para validar tokens FCM (formato aproximado)
                function ($attribute, $value, $fail) {
                    // Validar formato típico de FCM token
                    if (!preg_match('/^[A-Za-z0-9_-]{20,255}$/', $value)) {
                        $fail('O formato do token de dispositivo é inválido.');
                    }
                },
            ],
            'device_type' => 'required|in:android,ios,web', // Adicionar tipo de dispositivo
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $user = $request->user();
            $user->device_token = $request->device_token;
            $user->device_type = $request->device_type; // Guardar tipo de dispositivo
            $user->save();

            // Registrar informação em log
            \Illuminate\Support\Facades\Log::info('Token de dispositivo registrado', [
                'user_id' => $user->id,
                'device_type' => $request->device_type,
                'token_prefix' => substr($request->device_token, 0, 8) . '...' // Registrar apenas parte do token
            ]);

            return $this->successResponse('Token registrado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao registrar token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Inscreve o usuário em um tópico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribeTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            // Aqui você implementaria a lógica para inscrever o token em um tópico
            // Você pode armazenar essa informação no banco ou fazer diretamente via Firebase Admin SDK

            // Por enquanto, só logamos a operação
            \Log::info('Inscrevendo usuário em tópico', [
                'user_id' => $request->user()->id,
                'topic' => $request->topic
            ]);

            return $this->successResponse('Inscrito no tópico com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao inscrever no tópico: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancela a inscrição do usuário em um tópico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unsubscribeTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            // Aqui você implementaria a lógica para cancelar a inscrição do token em um tópico
            // Você pode atualizar essa informação no banco ou fazer diretamente via Firebase Admin SDK

            // Por enquanto, só logamos a operação
            \Log::info('Cancelando inscrição de usuário em tópico', [
                'user_id' => $request->user()->id,
                'topic' => $request->topic
            ]);

            return $this->successResponse('Inscrição no tópico cancelada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao cancelar inscrição no tópico: ' . $e->getMessage(), 500);
        }
    }
}
