<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $templates = NotificationTemplate::getDefaultTemplates();
            
            foreach ($templates as $template) {
                NotificationTemplate::updateOrCreate(
                    [
                        'code' => $template['code'],
                        'type' => $template['type']
                    ],
                    $template
                );
            }
        });
        
        $this->command->info('Notification templates seeded successfully.');
    }
}