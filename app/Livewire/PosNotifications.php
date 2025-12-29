<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PosNotifications extends Component
{
    public $unreadCount = 0;

    public function mount()
    {
        $this->unreadCount = Auth::user()->unreadNotifications()->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->unreadCount = Auth::user()->unreadNotifications()->count();
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->unreadCount = 0;
    }

    public $isOpen = false;

    public function render()
    {
        $user = Auth::user();
        $newCount = $user->unreadNotifications()->count();

        // Check if count increased (New Notification)
        if ($newCount > $this->unreadCount) {
            $this->dispatch('play-notification-sound');
        }

        $this->unreadCount = $newCount;

        $notifications = $this->isOpen
            ? $user->unreadNotifications()->latest()->take(10)->get()
            : collect();

        return view('livewire.pos-notifications', [
            'notifications' => $notifications,
        ]);
    }
}
