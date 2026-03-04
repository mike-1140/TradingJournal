
@props(['value', 'currency' => 'USD', 'colored' => false])

@php
    $value = (float) $value;
    $isZero = abs($value) < 0.00001;

    $currencyMap = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'];
    $displayCurrency = $currencyMap[strtoupper($currency)] ?? $currency;

    $colorClass = '';
    if ($colored) {
        if ($value > 0.00001) $colorClass = 'text-success';
        elseif ($value < -0.00001) $colorClass = 'text-danger';
    }
@endphp

<span {{ $attributes->merge(['class' => "fw-bold $colorClass"]) }}>
    @if($isZero)
        -
    @else
        {{ number_format($value, 2, ',', '.') }} {{ $displayCurrency }}
    @endif
</span>
