<?php

namespace App\Services;

use App\Models\Notification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $httpClient;
    private $apiUrl;
    private $accessToken;
    private $phoneNumberId;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    public function sendTextMessage($to, $message, $replyToMessageId = null)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];

        if ($replyToMessageId) {
            $payload['context'] = [
                'message_id' => $replyToMessageId
            ];
        }

        return $this->sendRequest($payload);
    }

    public function sendTemplateMessage($to, $templateName, $languageCode = 'es', $parameters = [])
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];

        if (!empty($parameters)) {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(function($param) {
                        return ['type' => 'text', 'text' => $param];
                    }, $parameters)
                ]
            ];
        }

        return $this->sendRequest($payload);
    }

    public function sendMediaMessage($to, $mediaType, $mediaUrl, $caption = null)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => $mediaType, // image, document, audio, video
            $mediaType => [
                'link' => $mediaUrl
            ]
        ];

        if ($caption && in_array($mediaType, ['image', 'document', 'video'])) {
            $payload[$mediaType]['caption'] = $caption;
        }

        return $this->sendRequest($payload);
    }

    public function sendInteractiveMessage($to, $type, $content)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'interactive',
            'interactive' => [
                'type' => $type, // button, list
                ...$content
            ]
        ];

        return $this->sendRequest($payload);
    }

    public function markAsRead($messageId)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId
        ];

        return $this->sendRequest($payload);
    }

    private function sendRequest($payload)
    {
        try {
            $response = $this->httpClient->post(
                "{$this->apiUrl}/{$this->phoneNumberId}/messages",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'message_id' => $body['messages'][0]['id'] ?? null,
                'response' => $body
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp API Error: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    private function formatPhoneNumber($phone)
    {
        // Remover caracteres no numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si no tiene código de país, agregar Colombia (+57)
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
            $phone = '57' . $phone;
        }

        return $phone;
    }

    public function validateWebhook($mode, $token, $challenge)
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }

        return false;
    }

    public function processWebhook($payload)
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if ($change['field'] === 'messages') {
                    $this->processMessageStatus($change['value']);
                }
            }
        }
    }

    private function processMessageStatus($value)
    {
        // Procesar estados de mensajes (delivered, read, failed)
        foreach ($value['statuses'] ?? [] as $status) {
            $messageId = $status['id'];
            $statusType = $status['status'];

            $notification = Notification::where('provider_message_id', $messageId)->first();

            if ($notification) {
                switch ($statusType) {
                    case 'delivered':
                        $notification->markAsDelivered();
                        break;
                    case 'read':
                        $notification->markAsRead();
                        break;
                    case 'failed':
                        $errorMessage = $status['errors'][0]['title'] ?? 'Unknown error';
                        $notification->markAsFailed($errorMessage);
                        break;
                }
            }
        }

        // Procesar mensajes entrantes
        foreach ($value['messages'] ?? [] as $message) {
            $this->processIncomingMessage($message);
        }
    }

    private function processIncomingMessage($message)
    {
        // Procesar mensajes entrantes (respuestas, comandos, etc.)
        $from = $message['from'];
        $messageId = $message['id'];
        $timestamp = $message['timestamp'];

        // Marcar como leído
        $this->markAsRead($messageId);

        // Procesar contenido del mensaje
        if (isset($message['text'])) {
            $text = $message['text']['body'];
            $this->processTextMessage($from, $text, $messageId);
        }

        // Log del mensaje entrante
        Log::info('WhatsApp incoming message', [
            'from' => $from,
            'message_id' => $messageId,
            'timestamp' => $timestamp,
            'message' => $message
        ]);
    }

    private function processTextMessage($from, $text, $messageId)
    {
        // Procesar comandos básicos
        $text = strtolower(trim($text));

        switch ($text) {
            case 'stop':
            case 'baja':
            case 'cancelar':
                $this->handleUnsubscribe($from);
                break;
            case 'help':
            case 'ayuda':
                $this->sendHelpMessage($from);
                break;
            default:
                // Procesar otros comandos o respuestas
                break;
        }
    }

    private function handleUnsubscribe($phone)
    {
        // Implementar lógica de desuscripción
        // Actualizar preferencias del usuario
        $this->sendTextMessage($phone, 'Has sido desuscrito de las notificaciones de WhatsApp.');
    }

    private function sendHelpMessage($phone)
    {
        $helpText = "🏫 *WL School - Ayuda*\n\n" .
                   "Comandos disponibles:\n" .
                   "• *STOP* - Desuscribirse\n" .
                   "• *AYUDA* - Ver este mensaje\n\n" .
                   "Para más información, contacta con tu escuela.";

        $this->sendTextMessage($phone, $helpText);
    }
}