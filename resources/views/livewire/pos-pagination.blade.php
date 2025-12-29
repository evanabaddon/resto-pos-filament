<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-lg opacity-50">
                        Previous
                    </span>
                @else
                    <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 leading-5 rounded-lg hover:text-slate-500 focus:outline-none focus:ring ring-violet-300 focus:border-violet-300 active:bg-slate-100 transition ease-in-out duration-150">
                        Previous
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-700 bg-white border border-slate-200 leading-5 rounded-lg hover:text-slate-500 focus:outline-none focus:ring ring-violet-300 focus:border-violet-300 active:bg-slate-100 transition ease-in-out duration-150">
                        Next
                    </button>
                @else
                    <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-slate-400 bg-white border border-slate-200 cursor-default leading-5 rounded-lg opacity-50">
                        Next
                    </span>
                @endif
            </div>

            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                <div class="flex gap-1.5 items-center bg-white p-1.5 rounded-full border border-slate-200/60 shadow-sm">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="@lang('pagination.previous')">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-300 bg-transparent cursor-default rounded-full leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </span>
                        </span>
                    @else
                        <button wire:click="previousPage" wire:loading.attr="disabled" rel="prev" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-500 bg-transparent rounded-full hover:bg-violet-50 hover:text-violet-600 focus:z-10 focus:outline-none focus:ring ring-violet-300 transition ease-in-out duration-150" aria-label="@lang('pagination.previous')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-transparent cursor-default leading-5 -mt-0.5">...</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center justify-center w-8 h-8 text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-indigo-600 rounded-full cursor-default leading-5 shadow-sm ring-2 ring-violet-100">{{ $page }}</span>
                                    </span>
                                @else
                                    <button wire:click="gotoPage({{ $page }})" class="relative inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-slate-600 bg-transparent rounded-full hover:bg-violet-50 hover:text-violet-700 focus:z-10 focus:outline-none focus:ring ring-violet-300 transition ease-in-out duration-150" aria-label="@lang('pagination.goto_page', ['page' => $page])">
                                        {{ $page }}
                                    </button>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <button wire:click="nextPage" wire:loading.attr="disabled" rel="next" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-500 bg-transparent rounded-full hover:bg-violet-50 hover:text-violet-600 focus:z-10 focus:outline-none focus:ring ring-violet-300 transition ease-in-out duration-150" aria-label="@lang('pagination.next')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    @else
                        <span aria-disabled="true" aria-label="@lang('pagination.next')">
                            <span class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-slate-300 bg-transparent cursor-default rounded-full leading-5" aria-hidden="true">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </span>
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
