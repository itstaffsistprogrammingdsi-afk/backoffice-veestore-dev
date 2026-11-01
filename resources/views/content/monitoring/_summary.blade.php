<div class="row g-3 mb-4">
  @foreach($items as $item)
    <div class="col-6 col-xl-3">
      <div class="monitor-metric">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <div class="monitor-icon"><i class="mdi {{ $item['icon'] ?? 'mdi-chart-box-outline' }}"></i></div>
          @if(!empty($item['tone']))<span class="status-pill {{ $item['tone'] }}">{{ $item['hint'] ?? '' }}</span>@endif
        </div>
        <div class="value">{{ number_format((int) $item['value'], 0, ',', '.') }}</div>
        <div class="label">{{ $item['label'] }}</div>
      </div>
    </div>
  @endforeach
</div>
