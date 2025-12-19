<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Tables;

use App\Models\DiscountCode;
use App\Models\SaleItem;
use App\Services\DeepSeekService;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tier.name')
                    ->label('Level')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Sedulur Tinetes' => 'warning',
                        'Sedulur' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('points_balance')
                    ->label('Poin')
                    ->sortable(),
                TextColumn::make('total_visits')
                    ->label('Kunjungan')
                    ->sortable(),
                TextColumn::make('last_visit_at')
                    ->label('Terakhir Datang')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_contacted_at')
                    ->label('Terakhir Followup')
                    ->since()
                    ->color(fn($state) => $state ? 'info' : 'gray')
                    ->sortable()
                    ->placeholder('Belum pernah'),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),

                // WhatsApp SOP Actions
                ActionGroup::make([

                    // 0. AI Personalized Message
                    Action::make('ai_personalized_wa')
                        ->label('AI Smart Message')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->modalHeading('AI Personalized Message')
                        ->modalDescription('AI akan menganalisis riwayat belanja pelanggan ini untuk membuat pesan yang unik.')
                        ->form(function ($record) {
                            $service = new DeepSeekService();
                            $settings = app(GeneralSettings::class);

                            // 1. Gather behavioral data
                            $favoriteItems = SaleItem::whereIn('sale_id', $record->sales()->pluck('id'))
                                ->select('product_name', DB::raw('SUM(quantity) as total_qty'))
                                ->groupBy('product_name')
                                ->orderByDesc('total_qty')
                                ->limit(3)
                                ->get();

                            $memberData = [
                                'nama' => $record->name,
                                'total_kunjungan' => $record->total_visits,
                                'total_belanja' => $record->total_spend,
                                'poin_saat_ini' => $record->points_balance,
                                'level_member' => $record->tier?->name ?? 'Reguler',
                                'terakhir_datang' => $record->last_visit_at?->diffForHumans() ?? 'Belum pernah',
                                'menu_paling_sering_dibeli' => $favoriteItems->pluck('product_name')->implode(', ') ?: 'Belum ada data',
                            ];

                            // 2. Gather Business Context (Settings & Active Discounts)
                            $activePromos = DiscountCode::where('is_active', true)
                                ->where(function ($q) {
                                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                                })
                                ->limit(3)
                                ->get(['code', 'name', 'type', 'value', 'min_purchase']);

                            $companyData = [
                                'app_name' => $settings->app_name,
                                'instagram' => $settings->app_instagram,
                                'tiktok' => $settings->app_tiktok,
                                'program_name' => $settings->loyalty_program_name,
                                'available_promos' => $activePromos->toArray(),
                            ];

                            // 3. Generate using AI
                            try {
                                $response = $service->generatePersonalizedMessage($memberData, $companyData, $settings->ai_crm_system_prompt);
                                $message = $response['choices'][0]['message']['content'] ?? "Halo Kak {$record->name}, apa kabar? Kami kangen nih! Ada menu baru lho di {$settings->app_name}.";
                            } catch (\Exception $e) {
                                $message = "Halo Kak {$record->name}, apa kabar? Terima kasih ya sudah jadi pelanggan setia kami di {$settings->app_name}. Sampai jumpa!";
                            }

                            return [
                                \Filament\Forms\Components\Textarea::make('message')
                                    ->label('Pesan dari AI')
                                    ->helperText('AI merangkai pesan ini khusus berdasarkan data belanja pelanggan.')
                                    ->default($message)
                                    ->rows(8)
                                    ->required()
                            ];
                        })
                        ->action(function ($data, $record, $livewire) {
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            }
                            $text = rawurlencode($data['message']);
                            $record->update(['last_contacted_at' => now()]);

                            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$text}";
                            $livewire->js("window.open('{$url}', '_blank')");
                        }),

                    // 1. Smart SOP Action
                    Action::make('wa_sop')
                        ->label('Sapaan Rutin (SOP)')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->modalHeading('Kirim Pesan WhatsApp (SOP)')
                        ->modalDescription('Pesan otomatis dibuat berdasarkan fase pelanggan (Baru/Repeat/Naik Tier). Silakan review sebelum kirim.')
                        ->form(function ($record) {
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $message = '';

                            // Logic Phase
                            $visits = $record->total_visits ?? 0;
                            $points = $record->points_balance ?? 0;

                            if ($visits <= 1) {
                                // Phase 1: First Visit
                                $message = $settings->wa_template_phase_1;
                            } elseif ($points >= 180 && $visits >= 10) {
                                // Phase 3: High Value / Tier Up
                                $message = $settings->wa_template_phase_3;
                            } else {
                                // Phase 2: Repeat
                                $message = $settings->wa_template_phase_2;
                            }

                            // Replace Variables
                            $message = str_replace('{name}', $record->name, $message);
                            $message = str_replace('{points}', $points, $message);

                            return [
                                \Filament\Forms\Components\Textarea::make('message')
                                    ->label('Pesan')
                                    ->default($message)
                                    ->rows(6)
                                    ->required()
                            ];
                        })
                        ->action(function ($data, $record, $livewire) {
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            }
                            $text = rawurlencode($data['message']);

                            // Update Timestamp
                            $record->update(['last_contacted_at' => now()]);

                            // Filament specific: Notification
                            \Filament\Notifications\Notification::make()
                                ->title('Membuka WhatsApp...')
                                ->success()
                                ->send();

                            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$text}";
                            $livewire->js("window.open('{$url}', '_blank')");
                        }),

                    // 2. Cheat Sheet - Benefit
                    Action::make('faq_benefit')
                        ->label('FAQ: Benefit Poin')
                        ->icon('heroicon-o-question-mark-circle')
                        ->action(function ($record, $livewire) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = rawurlencode($settings->wa_template_faq_benefit);
                            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$text}";
                            $livewire->js("window.open('{$url}', '_blank')");
                        }),

                    // 3. Cheat Sheet - Redemption
                    Action::make('faq_redemption')
                        ->label('FAQ: Penukaran')
                        ->icon('heroicon-o-gift')
                        ->action(function ($record, $livewire) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = rawurlencode($settings->wa_template_faq_redemption);
                            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$text}";
                            $livewire->js("window.open('{$url}', '_blank')");
                        }),

                    // 4. Cheat Sheet - Use Points
                    Action::make('faq_use_points')
                        ->label('FAQ: Pakai Sekarang')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($record, $livewire) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = rawurlencode($settings->wa_template_faq_use_points);
                            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$text}";
                            $livewire->js("window.open('{$url}', '_blank')");
                        }),

                    // 5. Reset Followup
                    Action::make('reset_followup')
                        ->label('Reset Status Followup')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Status Followup?')
                        ->modalDescription('Status "Terakhir Followup" akan dikembalikan ke "Belum pernah". Gunakan jika Anda tidak jadi mengirim pesan.')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => null]);
                            \Filament\Notifications\Notification::make()
                                ->title('Status Followup Direset')
                                ->success()
                                ->send();
                        }),

                ])
                    ->label('WhatsApp SOP')
                    ->icon('heroicon-m-chat-bubble-oval-left-ellipsis')
                    ->color('success'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
