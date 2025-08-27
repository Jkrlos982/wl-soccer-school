<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class School extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'subdomain',
        'logo',
        'theme_config',
        'is_active',
        'subscription_type',
        'subscription_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // Add any sensitive fields here if needed
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'theme_config' => 'array',
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'subscription_expires_at',
    ];

    /**
     * Subscription types available.
     */
    const SUBSCRIPTION_TYPES = [
        'free' => 'Free',
        'basic' => 'Basic',
        'premium' => 'Premium',
        'enterprise' => 'Enterprise',
    ];

    /**
     * Get the users for the school.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get active users for the school.
     */
    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    /**
     * Check if school is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if school subscription is active.
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->subscription_type === 'free') {
            return true;
        }

        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    /**
     * Check if school subscription is expired.
     *
     * @return bool
     */
    public function hasExpiredSubscription(): bool
    {
        if ($this->subscription_type === 'free') {
            return false;
        }

        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    /**
     * Get days until subscription expires.
     *
     * @return int|null
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->subscription_expires_at || $this->subscription_type === 'free') {
            return null;
        }

        return now()->diffInDays($this->subscription_expires_at, false);
    }

    /**
     * Get school's logo URL.
     *
     * @return string|null
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

    /**
     * Get school's full URL.
     *
     * @return string
     */
    public function getFullUrlAttribute(): string
    {
        return "https://{$this->subdomain}." . config('app.domain', 'wlschool.com');
    }

    /**
     * Get subscription type label.
     *
     * @return string
     */
    public function getSubscriptionTypeLabelAttribute(): string
    {
        return self::SUBSCRIPTION_TYPES[$this->subscription_type] ?? 'Unknown';
    }

    /**
     * Scope a query to only include active schools.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schools with active subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->where(function ($q) {
            $q->where('subscription_type', 'free')
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('subscription_expires_at')
                       ->where('subscription_expires_at', '>', now());
              });
        });
    }

    /**
     * Scope a query to find school by subdomain.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $subdomain
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySubdomain($query, string $subdomain)
    {
        return $query->where('subdomain', $subdomain);
    }

    /**
     * Scope a query to search schools by name.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('subdomain', 'like', "%{$search}%");
        });
    }

    /**
     * Get school statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->activeUsers()->count(),
            'verified_users' => $this->users()->whereNotNull('email_verified_at')->count(),
            'subscription_status' => $this->hasActiveSubscription() ? 'active' : 'expired',
            'days_until_expiration' => $this->getDaysUntilExpiration(),
        ];
    }

    /**
     * Get theme configuration with defaults.
     *
     * @return array
     */
    public function getThemeConfigWithDefaults(): array
    {
        $defaults = [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#64748B',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'text_color' => '#1F2937',
            'font_family' => 'Inter',
            'logo_position' => 'left',
            'sidebar_color' => '#F8FAFC',
        ];

        return array_merge($defaults, $this->theme_config ?? []);
    }

    /**
     * Update theme configuration.
     *
     * @param array $config
     * @return bool
     */
    public function updateThemeConfig(array $config): bool
    {
        $currentConfig = $this->theme_config ?? [];
        $newConfig = array_merge($currentConfig, $config);
        
        return $this->update(['theme_config' => $newConfig]);
    }

    /**
     * Extend subscription.
     *
     * @param int $days
     * @return bool
     */
    public function extendSubscription(int $days): bool
    {
        $currentExpiration = $this->subscription_expires_at ?? now();
        $newExpiration = $currentExpiration->addDays($days);
        
        return $this->update(['subscription_expires_at' => $newExpiration]);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'subdomain';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure subdomain is lowercase
        static::saving(function ($school) {
            $school->subdomain = strtolower($school->subdomain);
        });
    }
}