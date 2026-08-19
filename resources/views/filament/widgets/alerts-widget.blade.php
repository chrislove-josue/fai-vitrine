<div class="fai-widget-card">
    <div class="fai-widget-header">
        <span class="fai-widget-title">
            <x-heroicon-o-bell class="h-4 w-4 text-[#C9A24B]" />
            Alertes
        </span>
    </div>
    <div class="fai-widget-body">
        @foreach ($this->getAlerts() as $alert)
            <div class="fai-alert {{ $alert['type'] }}">
                <span class="fai-alert-icon">
                    @if ($alert['type'] === 'danger')
                        <x-heroicon-o-exclamation-circle class="h-5 w-5" />
                    @elseif ($alert['type'] === 'warning')
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                    @else
                        <x-heroicon-o-check-circle class="h-5 w-5" />
                    @endif
                </span>
                <div class="fai-alert-body">
                    <div class="fai-alert-title">{{ $alert['title'] }}</div>
                    <div class="fai-alert-text">{{ $alert['text'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
