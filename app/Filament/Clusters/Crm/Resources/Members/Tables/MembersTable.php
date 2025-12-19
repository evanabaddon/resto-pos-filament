<?php

namespace App\Filament\Clusters\Crm\Resources\Members\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                        ->action(function ($data, $record) {
                            $phone = $record->phone;
                            // Basic cleaner (62 format)
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            }

                            $text = urlencode($data['message']);
                            $url = "https://wa.me/{$phone}?text={$text}";

                            // Open in new tab using JS or Redirect
                            // Filament Actions run on server, so we use 'openUrlInNewTab' if available or return response
                            return Action::make('redirect')->url($url, true);
                        })
                        // Using 'url' method directly allows opening new tab
                        ->url(function ($record) {
                            // This is tricky because we want the modal to EDIT the text first.
                            // Filament 'action' runs after modal submit.
                            // To open URL after submit, we rely on the redirect return, or simpler:
                            return null;
                        })
                        ->action(function ($data, $record) {
                            $phone = $record->phone;
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            }
                            $text = urlencode($data['message']);

                            // Update Timestamp
                            $record->update(['last_contacted_at' => now()]);

                            // Filament specific: Notification + Browser event
                            \Filament\Notifications\Notification::make()
                                ->title('Membuka WhatsApp...')
                                ->success()
                                ->send();

                            // We can't easily open a new tab from server-side action without Livewire tricks.
                            // WORKAROUND: We will save the message or just assume the user copies it?
                            // BETTER: Redirect to the WA URL.
                            redirect()->away("https://wa.me/{$phone}?text={$text}");
                        }),

                    // 2. Cheat Sheet - Benefit
                    Action::make('faq_benefit')
                        ->label('FAQ: Benefit Poin')
                        ->icon('heroicon-o-question-mark-circle')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = $record->phone;
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = urlencode($settings->wa_template_faq_benefit);
                            redirect()->away("https://wa.me/{$phone}?text={$text}");
                        }),

                    // 3. Cheat Sheet - Redemption
                    Action::make('faq_redemption')
                        ->label('FAQ: Penukaran')
                        ->icon('heroicon-o-gift')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = $record->phone;
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = urlencode($settings->wa_template_faq_redemption);
                            redirect()->away("https://wa.me/{$phone}?text={$text}");
                        }),

                    // 4. Cheat Sheet - Use Points
                    Action::make('faq_use_points')
                        ->label('FAQ: Pakai Sekarang')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(\App\Settings\GeneralSettings::class);
                            $phone = $record->phone;
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            $text = urlencode($settings->wa_template_faq_use_points);
                            redirect()->away("https://wa.me/{$phone}?text={$text}");
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