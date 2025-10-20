<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CashSession;
use Filament\Notifications\Notification;

class PosCashInModal extends Component
{
    public $show = false;
    public $cashInHand = 0;

    protected $listeners = [
        'openCashInModal' => 'openModal',
        'checkCashSession' => 'checkSession'
    ];

    public function mount()
    {
        // Cek session saat component dimount
        $this->checkSession();
    }

    public function checkSession()
    {
        $session = CashSession::where('user_id', auth()->id())
                    ->where('status', 'open')
                    ->first();

        if (!$session) {
            $this->show = true;
        }
    }

    public function openModal()
    {
        $this->show = true;
        $this->cashInHand = 0;
    }

    public function confirmCashIn()
    {
        if ($this->cashInHand <= 0) {
            
            return;
        }

        $this->dispatch('cashInConfirmed', cashInHand: $this->cashInHand);
        $this->show = false;
    }

    public function cancelCashIn()
    {
        $this->dispatch('cashInCancelled');
        $this->show = false;
        
        // Redirect ke dashboard
        return redirect()->route('filament.admin.pages.dashboard');
    }

    public function render()
    {
        return view('livewire.pos-cash-in-modal');
    }
}