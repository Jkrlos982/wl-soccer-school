<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    /**
     * Get all system configurations.
     */
    public function index(): JsonResponse
    {
        $configurations = [
            'payroll' => [
                'minimum_wage' => config('payroll.minimum_wage', 1160000),
                'transport_subsidy' => config('payroll.transport_subsidy', 140606),
                'health_contribution_rate' => config('payroll.health_contribution_rate', 0.04),
                'pension_contribution_rate' => config('payroll.pension_contribution_rate', 0.04),
                'solidarity_fund_rate' => config('payroll.solidarity_fund_rate', 0.01),
                'employer_health_rate' => config('payroll.employer_health_rate', 0.085),
                'employer_pension_rate' => config('payroll.employer_pension_rate', 0.12),
                'arl_rate' => config('payroll.arl_rate', 0.00522),
                'compensation_fund_rate' => config('payroll.compensation_fund_rate', 0.04),
                'icbf_rate' => config('payroll.icbf_rate', 0.03),
                'sena_rate' => config('payroll.sena_rate', 0.02),
                'severance_rate' => config('payroll.severance_rate', 0.0833),
                'severance_interest_rate' => config('payroll.severance_interest_rate', 0.12),
                'service_bonus_rate' => config('payroll.service_bonus_rate', 0.0833),
                'vacation_rate' => config('payroll.vacation_rate', 0.0417)
            ],
            'tax' => [
                'uvt_value' => config('tax.uvt_value', 42412),
                'withholding_exempt_uvt' => config('tax.withholding_exempt_uvt', 95),
                'withholding_rates' => config('tax.withholding_rates', [
                    ['min' => 0, 'max' => 95, 'rate' => 0, 'fixed' => 0],
                    ['min' => 95, 'max' => 150, 'rate' => 0.19, 'fixed' => 0],
                    ['min' => 150, 'max' => 360, 'rate' => 0.28, 'fixed' => 10.45],
                    ['min' => 360, 'max' => 640, 'rate' => 0.33, 'fixed' => 69.25],
                    ['min' => 640, 'max' => 945, 'rate' => 0.35, 'fixed' => 161.65],
                    ['min' => 945, 'max' => 2300, 'rate' => 0.37, 'fixed' => 268.4],
                    ['min' => 2300, 'max' => null, 'rate' => 0.39, 'fixed' => 769.75]
                ])
            ],
            'company' => [
                'name' => config('company.name', 'Mi Empresa'),
                'nit' => config('company.nit', '900123456-1'),
                'address' => config('company.address', 'Calle 123 #45-67'),
                'city' => config('company.city', 'Bogotá'),
                'phone' => config('company.phone', '+57 1 234 5678'),
                'email' => config('company.email', 'info@miempresa.com'),
                'website' => config('company.website', 'https://miempresa.com'),
                'logo' => config('company.logo', null),
                'legal_representative' => config('company.legal_representative', 'Juan Pérez'),
                'economic_activity' => config('company.economic_activity', '6201')
            ],
            'leave' => [
                'vacation_days_per_year' => config('leave.vacation_days_per_year', 15),
                'sick_leave_days_per_year' => config('leave.sick_leave_days_per_year', 12),
                'maternity_leave_days' => config('leave.maternity_leave_days', 98),
                'paternity_leave_days' => config('leave.paternity_leave_days', 8),
                'bereavement_leave_days' => config('leave.bereavement_leave_days', 5),
                'study_leave_hours_per_week' => config('leave.study_leave_hours_per_week', 6)
            ],
            'attendance' => [
                'work_hours_per_day' => config('attendance.work_hours_per_day', 8),
                'work_days_per_week' => config('attendance.work_days_per_week', 5),
                'overtime_rate' => config('attendance.overtime_rate', 1.25),
                'night_overtime_rate' => config('attendance.night_overtime_rate', 1.75),
                'holiday_rate' => config('attendance.holiday_rate', 1.75),
                'sunday_rate' => config('attendance.sunday_rate', 1.75)
            ],
            'notifications' => [
                'email_enabled' => config('notifications.email_enabled', true),
                'sms_enabled' => config('notifications.sms_enabled', false),
                'slack_enabled' => config('notifications.slack_enabled', false),
                'payroll_completion_notify' => config('notifications.payroll_completion_notify', true),
                'leave_request_notify' => config('notifications.leave_request_notify', true),
                'birthday_notify' => config('notifications.birthday_notify', true)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $configurations,
            'message' => 'Configuraciones obtenidas exitosamente'
        ]);
    }

    /**
     * Get specific configuration section.
     */
    public function show(string $section): JsonResponse
    {
        $validSections = ['payroll', 'tax', 'company', 'leave', 'attendance', 'notifications'];
        
        if (!in_array($section, $validSections)) {
            return response()->json([
                'success' => false,
                'message' => 'Sección de configuración inválida'
            ], 422);
        }

        $configuration = config($section, []);

        return response()->json([
            'success' => true,
            'data' => $configuration,
            'message' => "Configuración de {$section} obtenida exitosamente"
        ]);
    }

    /**
     * Update system configurations.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'section' => 'required|string|in:payroll,tax,company,leave,attendance,notifications',
            'configurations' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        $section = $request->section;
        $configurations = $request->configurations;

        // Validate specific section configurations
        $sectionValidator = $this->validateSectionConfigurations($section, $configurations);
        
        if ($sectionValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Configuraciones inválidas para la sección',
                'errors' => $sectionValidator->errors()
            ], 422);
        }

        // Store configurations in cache and/or database
        foreach ($configurations as $key => $value) {
            $configKey = "{$section}.{$key}";
            Cache::put($configKey, $value, now()->addDays(30));
            
            // You might want to store in database for persistence
            // Configuration::updateOrCreate(
            //     ['key' => $configKey],
            //     ['value' => $value]
            // );
        }

        return response()->json([
            'success' => true,
            'data' => $configurations,
            'message' => "Configuraciones de {$section} actualizadas exitosamente"
        ]);
    }

    /**
     * Get payroll configuration.
     */
    public function payrollConfig(): JsonResponse
    {
        $config = [
            'minimum_wage' => config('payroll.minimum_wage', 1160000),
            'transport_subsidy' => config('payroll.transport_subsidy', 140606),
            'health_contribution_rate' => config('payroll.health_contribution_rate', 0.04),
            'pension_contribution_rate' => config('payroll.pension_contribution_rate', 0.04),
            'solidarity_fund_rate' => config('payroll.solidarity_fund_rate', 0.01),
            'employer_rates' => [
                'health' => config('payroll.employer_health_rate', 0.085),
                'pension' => config('payroll.employer_pension_rate', 0.12),
                'arl' => config('payroll.arl_rate', 0.00522),
                'compensation_fund' => config('payroll.compensation_fund_rate', 0.04),
                'icbf' => config('payroll.icbf_rate', 0.03),
                'sena' => config('payroll.sena_rate', 0.02)
            ],
            'social_benefits' => [
                'severance_rate' => config('payroll.severance_rate', 0.0833),
                'severance_interest_rate' => config('payroll.severance_interest_rate', 0.12),
                'service_bonus_rate' => config('payroll.service_bonus_rate', 0.0833),
                'vacation_rate' => config('payroll.vacation_rate', 0.0417)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Configuración de nómina obtenida exitosamente'
        ]);
    }

    /**
     * Get tax configuration.
     */
    public function taxConfig(): JsonResponse
    {
        $config = [
            'uvt_value' => config('tax.uvt_value', 42412),
            'withholding_exempt_uvt' => config('tax.withholding_exempt_uvt', 95),
            'withholding_rates' => config('tax.withholding_rates', [])
        ];

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Configuración de impuestos obtenida exitosamente'
        ]);
    }

    /**
     * Get company configuration.
     */
    public function companyConfig(): JsonResponse
    {
        $config = [
            'name' => config('company.name', 'Mi Empresa'),
            'nit' => config('company.nit', '900123456-1'),
            'address' => config('company.address', 'Calle 123 #45-67'),
            'city' => config('company.city', 'Bogotá'),
            'phone' => config('company.phone', '+57 1 234 5678'),
            'email' => config('company.email', 'info@miempresa.com'),
            'website' => config('company.website', 'https://miempresa.com'),
            'logo' => config('company.logo', null),
            'legal_representative' => config('company.legal_representative', 'Juan Pérez'),
            'economic_activity' => config('company.economic_activity', '6201')
        ];

        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Configuración de empresa obtenida exitosamente'
        ]);
    }

    /**
     * Reset configurations to default values.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'section' => 'required|string|in:payroll,tax,company,leave,attendance,notifications'
        ]);

        $section = $request->section;
        
        // Clear cache for the section
        $cacheKeys = Cache::get("config_keys_{$section}", []);
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget("config_keys_{$section}");

        return response()->json([
            'success' => true,
            'message' => "Configuraciones de {$section} restablecidas a valores por defecto"
        ]);
    }

    /**
     * Export configurations.
     */
    public function export(): JsonResponse
    {
        $configurations = [
            'payroll' => config('payroll', []),
            'tax' => config('tax', []),
            'company' => config('company', []),
            'leave' => config('leave', []),
            'attendance' => config('attendance', []),
            'notifications' => config('notifications', [])
        ];

        return response()->json([
            'success' => true,
            'data' => $configurations,
            'message' => 'Configuraciones exportadas exitosamente',
            'export_date' => now()->toISOString()
        ]);
    }

    /**
     * Import configurations.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'configurations' => 'required|array',
            'configurations.payroll' => 'sometimes|array',
            'configurations.tax' => 'sometimes|array',
            'configurations.company' => 'sometimes|array',
            'configurations.leave' => 'sometimes|array',
            'configurations.attendance' => 'sometimes|array',
            'configurations.notifications' => 'sometimes|array'
        ]);

        $configurations = $request->configurations;
        $imported = [];

        foreach ($configurations as $section => $sectionConfig) {
            if (is_array($sectionConfig)) {
                foreach ($sectionConfig as $key => $value) {
                    $configKey = "{$section}.{$key}";
                    Cache::put($configKey, $value, now()->addDays(30));
                    $imported[] = $configKey;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => ['imported_count' => count($imported)],
            'message' => 'Configuraciones importadas exitosamente'
        ]);
    }

    /**
     * Validate section-specific configurations.
     */
    private function validateSectionConfigurations(string $section, array $configurations)
    {
        $rules = [];

        switch ($section) {
            case 'payroll':
                $rules = [
                    'minimum_wage' => 'sometimes|numeric|min:0',
                    'transport_subsidy' => 'sometimes|numeric|min:0',
                    'health_contribution_rate' => 'sometimes|numeric|between:0,1',
                    'pension_contribution_rate' => 'sometimes|numeric|between:0,1',
                    'solidarity_fund_rate' => 'sometimes|numeric|between:0,1'
                ];
                break;
            case 'tax':
                $rules = [
                    'uvt_value' => 'sometimes|numeric|min:0',
                    'withholding_exempt_uvt' => 'sometimes|numeric|min:0'
                ];
                break;
            case 'company':
                $rules = [
                    'name' => 'sometimes|string|max:255',
                    'nit' => 'sometimes|string|max:20',
                    'email' => 'sometimes|email',
                    'phone' => 'sometimes|string|max:20'
                ];
                break;
            case 'leave':
                $rules = [
                    'vacation_days_per_year' => 'sometimes|integer|min:0|max:365',
                    'sick_leave_days_per_year' => 'sometimes|integer|min:0|max:365'
                ];
                break;
            case 'attendance':
                $rules = [
                    'work_hours_per_day' => 'sometimes|numeric|min:1|max:24',
                    'work_days_per_week' => 'sometimes|integer|min:1|max:7',
                    'overtime_rate' => 'sometimes|numeric|min:1'
                ];
                break;
            case 'notifications':
                $rules = [
                    'email_enabled' => 'sometimes|boolean',
                    'sms_enabled' => 'sometimes|boolean',
                    'slack_enabled' => 'sometimes|boolean'
                ];
                break;
        }

        return Validator::make($configurations, $rules);
    }
}