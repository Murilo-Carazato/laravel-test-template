<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook to configured destinations
     *
     * @param string $event Event type
     * @param array $payload Data to send
     * @return void
     */
    public static function dispatch(string $event, array $payload)
    {
        $webhookUrls = config('webhooks.destinations');
        
        foreach ($webhookUrls as $url) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => config('app.name') . ' Webhook',
                        'X-Webhook-Event' => $event,
                    ])
                    ->post($url, [
                        'event' => $event,
                        'payload' => $payload,
                        'timestamp' => now()->timestamp,
                    ]);
                
                if (!$response->successful()) {
                    Log::warning('Webhook delivery failed', [
                        'url' => $url,
                        'event' => $event,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Webhook dispatch exception', [
                    'url' => $url,
                    'event' => $event,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Validate a webhook signature
     *
     * @param \Illuminate\Http\Request $request
     * @param string $secret
     * @return bool
     */
    public static function validateSignature($request, $secret)
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}