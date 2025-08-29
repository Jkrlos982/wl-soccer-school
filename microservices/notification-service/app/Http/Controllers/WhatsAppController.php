<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WhatsAppController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle WhatsApp webhook verification and message processing
     */
    public function webhook(Request $request)
    {
        // Verificación del webhook
        if ($request->has(['hub_mode', 'hub_verify_token', 'hub_challenge'])) {
            $challenge = $this->whatsappService->validateWebhook(
                $request->hub_mode,
                $request->hub_verify_token,
                $request->hub_challenge
            );

            if ($challenge) {
                return response($challenge, 200);
            }

            return response('Forbidden', 403);
        }

        // Procesar webhook
        $payload = $request->all();
        $this->whatsappService->processWebhook($payload);

        return response('OK', 200);
    }

    /**
     * Send test message endpoint
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        $result = $this->whatsappService->sendTextMessage(
            $request->phone,
            $request->message
        );

        return response()->json($result);
    }

    /**
     * Send template message endpoint
     */
    public function sendTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'template_name' => 'required|string',
            'language_code' => 'string',
            'parameters' => 'array'
        ]);

        $result = $this->whatsappService->sendTemplateMessage(
            $request->phone,
            $request->template_name,
            $request->language_code ?? 'es',
            $request->parameters ?? []
        );

        return response()->json($result);
    }

    /**
     * Send media message endpoint
     */
    public function sendMedia(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'media_type' => 'required|string|in:image,document,audio,video',
            'media_url' => 'required|url',
            'caption' => 'string|nullable'
        ]);

        $result = $this->whatsappService->sendMediaMessage(
            $request->phone,
            $request->media_type,
            $request->media_url,
            $request->caption
        );

        return response()->json($result);
    }

    /**
     * Send interactive message endpoint
     */
    public function sendInteractive(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'type' => 'required|string|in:button,list',
            'content' => 'required|array'
        ]);

        $result = $this->whatsappService->sendInteractiveMessage(
            $request->phone,
            $request->type,
            $request->content
        );

        return response()->json($result);
    }

    /**
     * Mark message as read endpoint
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'message_id' => 'required|string'
        ]);

        $result = $this->whatsappService->markAsRead($request->message_id);

        return response()->json($result);
    }

    /**
     * Get webhook verification status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'service' => 'WhatsApp Business API',
            'status' => 'active',
            'timestamp' => now()->toISOString(),
            'webhook_configured' => !empty(config('services.whatsapp.webhook_verify_token'))
        ]);
    }
}