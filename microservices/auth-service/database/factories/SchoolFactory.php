<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->company . ' School';
        
        return [
            'name' => $name,
            'subdomain' => Str::slug($name) . '-' . $this->faker->randomNumber(3),
            'logo' => null,
            'description' => $this->faker->sentence,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'website' => $this->faker->url,
            'subscription_type' => $this->faker->randomElement(['free', 'basic', 'premium', 'enterprise']),
            'subscription_expires_at' => $this->faker->dateTimeBetween('now', '+2 years'),
            'is_active' => true,
            'theme_config' => null,
            'settings' => null,
        ];
    }

    /**
     * Indicate that the school should be inactive.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the school should have a specific subscription type.
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function subscriptionType(string $type)
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'subscription_type' => $type,
            ];
        });
    }

    /**
     * Indicate that the school subscription should be expired.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            ];
        });
    }
}