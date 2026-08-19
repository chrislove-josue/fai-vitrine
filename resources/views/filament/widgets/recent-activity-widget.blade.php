<div class="fai-widget-card" style="height:100%">
    <div class="fai-widget-header">
        <span class="fai-widget-title">
            <i class="bi bi-clock-history text-[#0B2545]"></i>
            Activité récente
        </span>
    </div>
    <div class="fai-widget-body" style="padding:0">
        @forelse ($this->getActivities() as $activity)
            <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1.25rem;border-bottom:1px solid #E4EAF2">
                <div style="width:8px;height:8px;border-radius:9999px;flex-shrink:0;background:{{ $activity['color'] }}"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:.75rem;font-weight:600;color:#10151F">{{ $activity['description'] }}</div>
                    <div style="font-size:.7rem;color:#667085;margin-top:.1rem">{{ $activity['date']->locale('fr')->diffForHumans() }}</div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:.82rem;font-weight:700;color:#10151F">{{ number_format($activity['amount'], 0, ',', ' ') }} XOF</div>
                    <div style="font-size:.65rem;font-weight:600;color:{{ $activity['color'] }};text-transform:uppercase;letter-spacing:.04em">{{ $activity['type'] }}</div>
                </div>
            </div>
        @empty
            <div style="padding:1.5rem;text-align:center;color:#667085;font-size:.85rem">
                Aucune activité récente.
            </div>
        @endforelse
    </div>
</div>
