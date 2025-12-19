<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Member;
use Filament\Notifications\Notification;

class PosCreateMemberModal extends Component
{
    public $show = false;
    public $name = '';
    public $phone = '';
    public $email = '';

    protected $listeners = ['openCreateMemberModal' => 'open'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:members,phone',
        'email' => 'nullable|email|max:255',
    ];

    public function open()
    {
        $this->show = true;
        // Reset form
        $this->reset(['name', 'phone', 'email']);
        $this->resetErrorBag();
    }

    public function close()
    {
        $this->show = false;
    }

    public function save()
    {
        $this->validate();

        // Cari Tier Default (Terendah)
        $defaultTier = \App\Models\LoyaltyTier::orderBy('min_visits', 'asc')
            ->orderBy('min_points', 'asc')
            ->first();

        $member = Member::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'points_balance' => 0,
            'join_date' => now(),
            'tier_id' => $defaultTier ? $defaultTier->id : null,
        ]);

        $this->dispatch('memberCreated', $member->id);

        $this->dispatch(
            'show-notification',
            message: "{$member->name} telah ditambahkan dan dipilih.",
            type: 'success'
        );

        $this->close();
    }

    public function render()
    {
        return view('livewire.pos-create-member-modal');
    }
}
