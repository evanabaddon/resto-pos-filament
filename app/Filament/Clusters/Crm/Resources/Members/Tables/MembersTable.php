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
use App\Filament\Pages\WhatsappCenter;
use Illuminate\Support\Facades\DB;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                PhoneColumn::make('phone')
                    ->label(__('messages.phone'))
                    ->searchable()
                    ->defaultCountry('ID')
                    ->displayFormat(PhoneInputNumberType::INTERNATIONAL)
                    ->copyable(),
                TextColumn::make('tier.name')
                    ->label(__('messages.tier'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Sedulur Tinetes' => 'warning',
                        'Sedulur' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('points_balance')
                    ->label(__('messages.points'))
                    ->sortable(),
                TextColumn::make('total_visits')
                    ->label(__('messages.visits'))
                    ->sortable(),
                TextColumn::make('last_visit_at')
                    ->label(__('messages.last_visit'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_contacted_at')
                    ->label(__('messages.last_contacted'))
                    ->since()
                    ->color(fn($state) => $state ? 'info' : 'gray')
                    ->sortable()
                    ->placeholder(__('messages.never')),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),

                // WhatsApp SOP Actions
                ActionGroup::make([

                    // 0. AI Personalized Message
                    Action::make('ai_personalized_wa')
                        ->label(__('messages.wa_ai_smart'))
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->modalHeading(__('messages.ai_personalized_heading'))
                        ->modalDescription(__('messages.ai_personalized_desc'))
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

                            // 3. Gather Top Menu (Knowledge Base)
                            $topItems = SaleItem::query()
                                ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
                                ->join('products', 'sale_items.product_id', '=', 'products.id')
                                ->where('products.is_sellable', true)
                                ->where('products.name', '!=', 'Down Payment (DP)')
                                ->groupBy('product_id')
                                ->orderByDesc('total_qty')
                                ->limit(5)
                                ->get();

                            $menuList = $topItems->map(fn($item) => $item->product?->name)->filter()->toArray();

                            $companyData = [
                                'app_name' => $settings->app_name,
                                'instagram' => $settings->app_instagram,
                                'tiktok' => $settings->app_tiktok,
                                'program_name' => $settings->loyalty_program_name,
                                'available_promos' => $activePromos->toArray(),
                                'top_menu' => $menuList,
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
                                    ->label(__('messages.ai_message_label'))
                                    ->helperText(__('messages.ai_message_helper'))
                                    ->default($message)
                                    ->rows(8)
                                    ->required()
                            ];
                        })
                        ->action(function ($data, $record) {
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            } elseif (substr($phone, 0, 1) == '8') {
                                $phone = '62' . $phone;
                            }
                            $jid = $phone . '@s.whatsapp.net';
                            $record->update(['last_contacted_at' => now()]);

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $data['message']
                            ]));
                        }),

                    // 0.5. Re-engage (AI with Preview)
                    Action::make('re_engage_ai')
                        ->label(__('messages.wa_ai_reengage'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->modalHeading(__('messages.ai_reengage_heading'))
                        ->modalDescription(__('messages.ai_reengage_desc'))
                        ->form(function ($record) {
                            $service = new DeepSeekService();
                            $settings = app(GeneralSettings::class);

                            // Gather member data
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

                            // Gather business context
                            $activePromos = DiscountCode::where('is_active', true)
                                ->where(function ($q) {
                                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                            })
                                ->limit(3)
                                ->get(['code', 'name', 'type', 'value', 'min_purchase']);

                            $topItems = SaleItem::query()
                                ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
                                ->join('products', 'sale_items.product_id', '=', 'products.id')
                                ->where('products.is_sellable', true)
                                ->where('products.name', '!=', 'Down Payment (DP)')
                                ->groupBy('product_id')
                                ->orderByDesc('total_qty')
                                ->limit(5)
                                ->get();

                            $menuList = $topItems->map(fn($item) => $item->product?->name)->filter()->toArray();

                            $companyData = [
                                'app_name' => $settings->app_name,
                                'instagram' => $settings->app_instagram,
                                'tiktok' => $settings->app_tiktok,
                                'program_name' => $settings->loyalty_program_name,
                                'available_promos' => $activePromos->toArray(),
                                'top_menu' => $menuList,
                            ];

                            // Generate AI message for re-engagement
                            try {
                                // Use specific re-engagement prompt
                                $reEngagementPrompt = "Buat pesan WhatsApp untuk re-engagement pelanggan yang sudah lama tidak datang. Fokus pada:\n1. Sapaan hangat dan personal\n2. Menyampaikan kerinduan\n3. Highlight menu favorit mereka\n4. Tawarkan promo jika ada\n5. Ajakan untuk datang kembali\n\nTone: Hangat, personal, tidak pushy.";

                                $response = $service->generatePersonalizedMessage($memberData, $companyData, $reEngagementPrompt);
                                $message = $response['choices'][0]['message']['content'] ?? "Halo Kak {$record->name}, kami kangen nih! Sudah lama tidak mampir ke {$settings->app_name}. Yuk main lagi! 😊";
                            } catch (\Exception $e) {
                                $message = "Halo Kak {$record->name}, apa kabar? Kami kangen nih! Sudah lama tidak mampir ke {$settings->app_name}. Ada menu baru lho! Yuk main lagi! 😊";
                            }

                            return [
                                \Filament\Forms\Components\Textarea::make('message')
                                    ->label(__('messages.ai_reengage_label'))
                                    ->helperText(__('messages.ai_reengage_helper'))
                                    ->default($message)
                                    ->rows(8)
                                    ->required()
                            ];
                        })
                        ->action(function ($data, $record) {
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            } elseif (substr($phone, 0, 1) == '8') {
                                $phone = '62' . $phone;
                            }
                            $jid = $phone . '@s.whatsapp.net';
                            $record->update(['last_contacted_at' => now()]);

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $data['message']
                            ]));
                        }),

                    // 1. Smart SOP Action
                    Action::make('wa_sop')
                        ->label(__('messages.wa_sop'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->modalHeading(__('messages.wa_sop_heading'))
                        ->modalDescription(__('messages.wa_sop_desc'))
                        ->form(function ($record) {
                            $settings = app(GeneralSettings::class);
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
                                    ->label(__('messages.message'))
                                    ->default($message)
                                    ->rows(6)
                                    ->required()
                            ];
                        })
                        ->action(function ($data, $record) {
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0') {
                                $phone = '62' . substr($phone, 1);
                            } elseif (substr($phone, 0, 1) == '8') {
                                $phone = '62' . $phone;
                            }
                            $jid = $phone . '@s.whatsapp.net';

                            // Update Timestamp
                            $record->update(['last_contacted_at' => now()]);

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $data['message']
                            ]));
                        }),

                    // 2. Cheat Sheet - Benefit
                    Action::make('faq_benefit')
                        ->label(__('messages.wa_faq_benefit'))
                        ->icon('heroicon-o-question-mark-circle')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            elseif (substr($phone, 0, 1) == '8')
                                $phone = '62' . $phone;
                            $jid = $phone . '@s.whatsapp.net';

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $settings->wa_template_faq_benefit
                            ]));
                        }),

                    // 3. Cheat Sheet - Redemption
                    Action::make('faq_redemption')
                        ->label(__('messages.wa_faq_redemption'))
                        ->icon('heroicon-o-gift')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            elseif (substr($phone, 0, 1) == '8')
                                $phone = '62' . $phone;
                            $jid = $phone . '@s.whatsapp.net';

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $settings->wa_template_faq_redemption
                            ]));
                        }),

                    // 4. Cheat Sheet - Use Points
                    Action::make('faq_use_points')
                        ->label(__('messages.wa_faq_use_points'))
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => now()]);
                            $settings = app(GeneralSettings::class);
                            $phone = preg_replace('/[^0-9]/', '', $record->phone);
                            if (substr($phone, 0, 1) == '0')
                                $phone = '62' . substr($phone, 1);
                            elseif (substr($phone, 0, 1) == '8')
                                $phone = '62' . $phone;
                            $jid = $phone . '@s.whatsapp.net';

                            return redirect()->to(WhatsappCenter::getUrl([
                                'jid' => $jid,
                                'message' => $settings->wa_template_faq_use_points
                            ]));
                        }),

                    // 5. Reset Followup
                    Action::make('reset_followup')
                        ->label(__('messages.wa_reset_followup'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('messages.reset_followup_heading'))
                        ->modalDescription(__('messages.reset_followup_desc'))
                        ->action(function ($record) {
                            $record->update(['last_contacted_at' => null]);
                            \Filament\Notifications\Notification::make()
                                ->title(__('messages.followup_reset_success'))
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
                    DeleteBulkAction::make()
                        ->hidden(fn() => !in_array(auth()->user()->role, ['super_admin', 'admin'])),
                ]),
            ]);
    }
}
