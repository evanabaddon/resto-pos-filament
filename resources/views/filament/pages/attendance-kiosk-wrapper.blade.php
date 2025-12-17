<div class="w-full h-screen -m-8">
    {{-- Render the Livewire component directly.
    Note: The Livewire component has a layout defined in render() for standalone usage.
    When included here, we might get double layouts if not careful.
    However, purely including <livewire:attendance-kiosk /> mainly renders the template.
    The layout() call in render() usually applies only to full page renders.
    --}}
    <livewire:attendance-kiosk />
</div>