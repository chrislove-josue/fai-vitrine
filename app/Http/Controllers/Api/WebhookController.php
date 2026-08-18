<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\ExternalSystem;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController
{
    public function __construct(private readonly WebhookService $webhooks) {}

    /**
     * Point d'entrée des webhooks sortants des systèmes externes.
     *
     * Authentification par signature HMAC-SHA256 (en-tête X-Webhook-Signature)
     * contre le secret du système externe identifié par X-System-Code.
     */
    public function incoming(Request $request): JsonResponse
    {
        $systemCode = $request->header('X-System-Code');
        $signature = $request->header('X-Webhook-Signature');

        if ($systemCode === null || $signature === null) {
            return response()->json(['error' => 'En-têtes X-System-Code et X-Webhook-Signature requis.'], 400);
        }

        $system = ExternalSystem::where('code', $systemCode)->where('status', 'active')->first();

        if ($system === null) {
            return response()->json(['error' => 'Système externe inconnu ou inactif.'], 401);
        }

        $payload = $request->getContent();
        $event = $request->header('X-Event', data_get(json_decode($payload, true) ?? [], 'event'));
        $externalId = $request->header('X-Event-Id', data_get(json_decode($payload, true) ?? [], 'external_id'));

        if ($event === null) {
            return response()->json(['error' => 'Événement non identifié.'], 422);
        }

        $result = $this->webhooks->receive(
            $system,
            $event,
            $externalId,
            $payload,
            $signature,
            $request->ip(),
        );

        AuditLog::create([
            'action' => 'webhook.'.$result['status'],
            'ip_address' => $request->ip(),
            'metadata' => [
                'event' => $event,
                'system_code' => $systemCode,
                'external_id' => $externalId,
                'receipt_uuid' => $result['receipt']->uuid,
            ],
        ]);

        return match ($result['status']) {
            'signature_failed' => response()->json(['error' => 'Signature invalide.'], 401),
            default => response()->json(['status' => $result['status'], 'receipt' => $result['receipt']->uuid]),
        };
    }
}
