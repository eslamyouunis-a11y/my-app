@php
    use Picqer\Barcode\BarcodeGeneratorSVG;

    $record = $getRecord();
    $barcode = $record?->barcode ? (string) $record->barcode : null;
    $barcodeValue = $barcode ?? '';

    $labelCode = "\u{0627}\u{0644}\u{0643}\u{0648}\u{062f}";
    $labelBarcode = "\u{0627}\u{0644}\u{0628}\u{0627}\u{0631}\u{0643}\u{0648}\u{062f}";
    $labelCopy = "\u{0646}\u{0633}\u062e";
    $labelCopied = "\u{062a}\u0645 \u{0627}\u{0644}\u{0646}\u{0633}\u{062e}";

    $barcodeSvg = null;
    if ($barcode) {
        $generator = new BarcodeGeneratorSVG();
        $barcodeSvg = $generator->getBarcode($barcode, $generator::TYPE_CODE_128, 3, 30);
        $barcodeSvg = preg_replace('/^<\?xml.*?\?>\s*<!DOCTYPE.*?>\s*/s', '', $barcodeSvg);
    }
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:items-start md:pt-0" dir="ltr">
    <div class="hidden md:block md:col-start-1"></div>

    <div class="flex flex-col items-center gap-2 md:col-start-2 md:self-start" dir="rtl">
        <div class="text-sm leading-none text-gray-500">{{ $labelBarcode }}</div>
        @if ($barcodeSvg)
            <div class="flex justify-center">{!! $barcodeSvg !!}</div>
            <div class="text-xs text-gray-500">{{ $barcode }}</div>
        @else
            <div class="text-sm text-gray-400">-</div>
        @endif
    </div>

    <div class="flex flex-col items-end gap-2 md:col-start-3 md:justify-self-end justify-self-end md:self-start" dir="rtl">
        <div class="text-sm leading-none text-gray-500">{{ $labelCode }}</div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-600 transition hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-orange-200"
            x-on:click="if (navigator.clipboard && @js($barcodeValue)) { navigator.clipboard.writeText(@js($barcodeValue)); }"
            aria-label="{{ $labelCopy }}"
            title="{{ $labelCopy }}"
        >
            <x-filament::icon icon="heroicon-m-clipboard" class="h-4 w-4" />
            <span>{{ $barcode ?? '-' }}</span>
        </button>
    </div>
</div>
