<x-filament-panels::page>
    @php
        $courier = $record->loadMissing([
            'branch',
            'governorate',
            'area',
            'commission',
            'user',
        ]);

        $commission = $courier->commission;

        $avatar = 'https://ui-avatars.com/api/?name='
            . urlencode($courier->full_name)
            . '&background=0F172A&color=fff&size=256';
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="rounded-2xl bg-white shadow ring-1 ring-gray-200 p-6 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="{{ $avatar }}" class="w-20 h-20 rounded-xl object-cover" />

                <div>
                    <h2 class="text-xl font-bold">
                        {{ $courier->full_name }}
                    </h2>

                    <div class="text-sm text-gray-600 mt-1 space-x-4">
                        <span>الكود: <b>{{ $courier->courier_code }}</b></span>
                        <span>الهاتف: {{ $courier->phone }}</span>
                        <span>الفرع: {{ $courier->branch?->name }}</span>
                    </div>

                    <div class="text-sm text-gray-500 mt-1">
                        {{ $courier->governorate?->name }} - {{ $courier->area?->name }}
                    </div>
                </div>
            </div>

            <span class="px-3 py-1 rounded-full text-sm font-bold
                {{ $courier->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $courier->is_active ? 'مفعل' : 'غير مفعل' }}
            </span>
        </div>

        {{-- Financial --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <h3 class="font-bold mb-2">بيانات الدخول</h3>
                <p>الإيميل: {{ $courier->user?->email }}</p>
            </x-filament::section>

            <x-filament::section>
                <h3 class="font-bold mb-2">المركبة</h3>
                <p>النوع: {{ $courier->vehicle_type }}</p>
                <p>رخصة القيادة: {{ $courier->driving_license_expiry }}</p>
            </x-filament::section>

            <x-filament::section>
                <h3 class="font-bold mb-2">الطوارئ</h3>
                <p>{{ $courier->emergency_name }}</p>
                <p>{{ $courier->emergency_phone_1 }}</p>
            </x-filament::section>
        </div>

        {{-- Commissions --}}
        <div class="rounded-2xl bg-white shadow ring-1 ring-gray-200 p-6">
            <h3 class="text-lg font-bold mb-4">العمولات</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-gray-50">
                    <b>التسليم</b>
                    <p>قيمة: {{ $commission->delivery_value ?? 0 }}</p>
                    <p>نسبة: {{ $commission->delivery_percentage ?? 0 }}%</p>
                </div>

                <div class="p-4 rounded-xl bg-gray-50">
                    <b>مدفوع</b>
                    <p>قيمة: {{ $commission->paid_value ?? 0 }}</p>
                    <p>نسبة: {{ $commission->paid_percentage ?? 0 }}%</p>
                </div>

                <div class="p-4 rounded-xl bg-gray-50">
                    <b>على الراسل</b>
                    <p>قيمة: {{ $commission->sender_return_value ?? 0 }}</p>
                    <p>نسبة: {{ $commission->sender_return_percentage ?? 0 }}%</p>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
