<?php

namespace App\Livewire;

use Livewire\Component;

class CashSummaryButton extends Component
{
    public $hasActiveSession = false;

    protected $listeners = ['cashSessionUpdated' => 'checkSession'];

    public function mount()
    {
        $this->checkSession();
    }

    public function checkSession()
    {
        $this->hasActiveSession = session()->has('cash_session_id');
    }

    public function openCashSummary()
    {
        if (!$this->hasActiveSession) {
            $this->dispatch('showNotification', [
                'message' => 'Tidak ada sesi kas yang aktif',
                'type' => 'warning'
            ]);
            return;
        }

        $this->dispatch('openCashSummaryModal');
    }

    public function render()
    {
        return view('livewire.cash-summary-button');
    }
}
