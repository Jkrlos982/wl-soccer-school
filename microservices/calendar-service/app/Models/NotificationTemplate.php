<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
        'language',
        'priority',
        'school_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the school that owns the template
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who created the template
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the template
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get templates by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get templates by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to get templates for a specific school
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where(function($q) use ($schoolId) {
            $q->where('school_id', $schoolId)
              ->orWhereNull('school_id'); // Global templates
        });
    }

    /**
     * Replace variables in the template content
     */
    public function renderContent(array $variables = [])
    {
        $content = $this->body;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Replace variables in the template subject
     */
    public function renderSubject(array $variables = [])
    {
        $subject = $this->subject;
        
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
        }
        
        return $subject;
    }

    /**
     * Get available variable placeholders
     */
    public function getAvailableVariables()
    {
        return $this->variables ?? [];
    }

    /**
     * Validate that all required variables are provided
     */
    public function validateVariables(array $providedVariables)
    {
        $requiredVariables = $this->variables ?? [];
        $missingVariables = [];
        
        foreach ($requiredVariables as $variable) {
            if ($variable['required'] && !isset($providedVariables[$variable['name']])) {
                $missingVariables[] = $variable['name'];
            }
        }
        
        return $missingVariables;
    }

    /**
     * Get default templates for seeding
     */
    public static function getDefaultTemplates()
    {
        return [
            [
                'code' => 'training_reminder',
                'name' => 'Recordatorio de Entrenamiento',
                'type' => 'event_reminder',
                'channel' => 'whatsapp',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '🏃‍♂️ *Recordatorio de Entrenamiento*\n\nHola {{attendee_name}},\n\nTe recordamos que tienes entrenamiento programado:\n\n📅 **Fecha:** {{event_date}}\n🕐 **Hora:** {{event_time}}\n📍 **Lugar:** {{event_location}}\n\n¡No faltes! Tu equipo te espera.\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'event_time', 'required' => true, 'description' => 'Hora del evento'],
                    ['name' => 'event_location', 'required' => false, 'description' => 'Ubicación del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'match_reminder',
                'name' => 'Recordatorio de Partido',
                'type' => 'event_reminder',
                'channel' => 'whatsapp',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '⚽ *Recordatorio de Partido*\n\nHola {{attendee_name}},\n\nTe recordamos que tienes partido programado:\n\n📅 **Fecha:** {{event_date}}\n🕐 **Hora:** {{event_time}}\n📍 **Lugar:** {{event_location}}\n\n¡Prepárate para dar lo mejor de ti!\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'event_time', 'required' => true, 'description' => 'Hora del evento'],
                    ['name' => 'event_location', 'required' => false, 'description' => 'Ubicación del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'tournament_reminder',
                'name' => 'Recordatorio de Torneo',
                'type' => 'event_reminder',
                'channel' => 'whatsapp',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '🏆 *Recordatorio de Torneo*\n\nHola {{attendee_name}},\n\nTe recordamos que tienes torneo programado:\n\n📅 **Fecha:** {{event_date}}\n🕐 **Hora:** {{event_time}}\n📍 **Lugar:** {{event_location}}\n\n¡Es hora de brillar! 🌟\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'event_time', 'required' => true, 'description' => 'Hora del evento'],
                    ['name' => 'event_location', 'required' => false, 'description' => 'Ubicación del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'meeting_reminder',
                'name' => 'Recordatorio de Reunión',
                'type' => 'event_reminder',
                'channel' => 'email',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '<h2>Recordatorio de Reunión</h2><p>Hola {{attendee_name}},</p><p>Te recordamos que tienes una reunión programada:</p><ul><li><strong>Fecha:</strong> {{event_date}}</li><li><strong>Hora:</strong> {{event_time}}</li><li><strong>Lugar:</strong> {{event_location}}</li></ul><p>Por favor, confirma tu asistencia.</p><p><em>Enviado desde {{calendar_name}}</em></p>',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'event_time', 'required' => true, 'description' => 'Hora del evento'],
                    ['name' => 'event_location', 'required' => false, 'description' => 'Ubicación del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'payment_reminder',
                'name' => 'Recordatorio de Pago',
                'type' => 'payment_reminder',
                'channel' => 'whatsapp',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '💰 *Recordatorio de Pago*\n\nHola {{attendee_name}},\n\nTe recordamos que tienes un pago pendiente:\n\n📅 **Fecha límite:** {{event_date}}\n💵 **Concepto:** {{event_title}}\n\nPor favor, realiza tu pago a tiempo para evitar inconvenientes.\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'birthday_reminder',
                'name' => 'Recordatorio de Cumpleaños',
                'type' => 'birthday_reminder',
                'channel' => 'whatsapp',
                'subject' => '¡Feliz Cumpleaños!',
                'body' => '🎂 *¡Feliz Cumpleaños!* 🎉\n\n¡Hoy es el cumpleaños de {{attendee_name}}!\n\n🎈 Deseamos que tengas un día lleno de alegría y bendiciones.\n\n¡Que cumplas muchos más! 🥳\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del cumpleañero'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ],
            [
                'code' => 'general_event_reminder',
                'name' => 'Recordatorio General de Evento',
                'type' => 'event_reminder',
                'channel' => 'whatsapp',
                'subject' => 'Recordatorio: {{event_title}}',
                'body' => '📅 *Recordatorio de Evento*\n\nHola {{attendee_name}},\n\nTe recordamos que tienes el siguiente evento:\n\n📋 **Evento:** {{event_title}}\n📅 **Fecha:** {{event_date}}\n🕐 **Hora:** {{event_time}}\n📍 **Lugar:** {{event_location}}\n\n¡No olvides asistir!\n\n_Enviado desde {{calendar_name}}_',
                'variables' => [
                    ['name' => 'attendee_name', 'required' => true, 'description' => 'Nombre del asistente'],
                    ['name' => 'event_title', 'required' => true, 'description' => 'Título del evento'],
                    ['name' => 'event_date', 'required' => true, 'description' => 'Fecha del evento'],
                    ['name' => 'event_time', 'required' => true, 'description' => 'Hora del evento'],
                    ['name' => 'event_location', 'required' => false, 'description' => 'Ubicación del evento'],
                    ['name' => 'calendar_name', 'required' => true, 'description' => 'Nombre del calendario']
                ],
                'is_active' => true
            ]
        ];
    }
}