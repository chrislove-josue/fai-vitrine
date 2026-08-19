<div class="fai-widget-card">
    <div class="fai-widget-header">
        <span class="fai-widget-title">
            <i class="bi bi-bell text-[#C9A24B]"></i>
            Alertes
        </span>
    </div>
    <div class="fai-widget-body">
        @foreach ($this->getAlerts() as $alert)
            <div class="fai-alert {{ $alert['type'] }}">
                <span class="fai-alert-icon">
                    @if ($alert['type'] === 'danger')
                        <i class="bi bi-exclamation-circle text-lg"></i>
                    @elseif ($alert['type'] === 'warning')
                        <i class="bi bi-exclamation-triangle text-lg"></i>
                    @else
                        <i class="bi bi-check-circle text-lg"></i>
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
