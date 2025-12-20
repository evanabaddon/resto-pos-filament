<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddAiAssistantNameToGeneralSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('app.ai_assistant_name', 'Sarah (AI Admin)');
    }

    public function down(): void
    {
        $this->migrator->delete('app.ai_assistant_name');
    }
}
;
