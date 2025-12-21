<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('app.ai_model', 'deepseek-chat');
        $this->migrator->add('app.ai_base_url', 'https://api.deepseek.com');
        $this->migrator->add('app.ai_api_key', '');
    }
};
