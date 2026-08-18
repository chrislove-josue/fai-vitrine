@php $stats = $this->getStats(); @endphp

{{-- HERO : Salutation + date/support --}}
<div class="fai-hero-banner">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-2xl">
            <p class="fai-eyebrow">JENY SAS &middot; Bénin</p>
            <h2 class="mt-2 text-xl font-bold text-white sm:text-2xl">
                Bonjour, {{ auth()->user()->name }}
            </h2>
            <p class="mt-1 text-sm leading-relaxed text-white/75">
                Voici un aperçu de votre activité. Gérez clients, offres, abonnements,
                facturation et synchronisation réseau.
            </p>
        </div>
        <div class="flex items-center gap-4">
            <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-2 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#F7941D]">Date</p>
                <p class="text-xs font-bold text-white">{{ now()->locale('fr')->translatedFormat('d/m/Y') }}</p>
            </div>
            <div class="rounded-lg border border-white/15 bg-white/10 px-4 py-2 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#F7941D]">Support</p>
                <p class="text-xs font-bold text-white">7/7 · 24h</p>
            </div>
        </div>
    </div>
</div>

{{-- STATS : Container séparé --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4">
    <div class="rounded-xl border border-[#E4EAF2] bg-white p-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0057B8]/10">
                <x-heroicon-o-banknotes class="h-4 w-4 text-[#0057B8]" />
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#667085]">Revenus total</span>
        </div>
        <div class="mt-2 text-lg font-bold tabular-nums text-[#172033]">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} <span class="text-xs font-medium text-[#667085]">XOF</span></div>
        @if ($stats['revenue_trend'] != 0)
            <div class="mt-1 flex items-center gap-1">
                @if ($stats['revenue_trend'] > 0)
                    <span class="text-[10px] font-semibold text-[#12B76A]">▲ +{{ $stats['revenue_trend'] }}%</span>
                @else
                    <span class="text-[10px] font-semibold text-[#D92D20]">▼ {{ $stats['revenue_trend'] }}%</span>
                @endif
                <span class="text-[10px] text-[#667085]">vs mois dernier</span>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-[#E4EAF2] bg-white p-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#12B76A]/10">
                <x-heroicon-o-play-circle class="h-4 w-4 text-[#12B76A]" />
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#667085]">Abonnés actifs</span>
        </div>
        <div class="mt-2 text-lg font-bold tabular-nums text-[#172033]">{{ $stats['active_subscriptions'] }}</div>
    </div>

    <div class="rounded-xl border border-[#E4EAF2] bg-white p-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#007BFF]/10">
                <x-heroicon-o-users class="h-4 w-4 text-[#007BFF]" />
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#667085]">Clients</span>
        </div>
        <div class="mt-2 text-lg font-bold tabular-nums text-[#172033]">{{ $stats['total_customers'] }}</div>
    </div>

    <div class="rounded-xl border border-[#E4EAF2] bg-white p-4 shadow-[0_2px_8px_rgba(16,24,40,0.05)]">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F7941D]/10">
                <x-heroicon-o-document-text class="h-4 w-4 text-[#F7941D]" />
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#667085]">Factures en attente</span>
        </div>
        <div class="mt-2 text-lg font-bold tabular-nums text-[#172033]">{{ $stats['pending_invoices'] }}</div>
    </div>
</div>
