@props(['routeParameters' => []])

@if(!empty($routeParameters))
    <div {{ $attributes->merge(['class' => 'grid gap-2']) }}>
        @foreach($routeParameters as $key => $value)
            <div class="text-xs">
                <span class="font-semibold">{{ $key }}</span>:
                <span class="font-mono">{{ is_scalar($value) ? $value : json_encode($value) }}</span>
            </div>
        @endforeach
    </div>
@endif
