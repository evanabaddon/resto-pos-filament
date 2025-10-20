<?php

namespace App\Livewire;

use Livewire\Component;

class PosNotification extends Component
{
    public string $message = '';
    public string $type = 'info'; // success, error, warning, info
    public bool $visible = false;

    protected $listeners = ['showNotification' => 'show'];

    public function show(string $message, string $type = 'info')
    {
        $this->message = $message;
        $this->type = $type;
        $this->visible = true;

        // Kirim event ke browser agar bisa auto-hide
        $this->dispatch('hide-notification', timeout: 3000);
    }

    public function render()
    {
        return view('livewire.pos-notification');
    }
}
