<x-filament-panels::page>
    @php
        $merchant = $record->loadMissing([
            'branch',
            'governorate',
            'area',
            'user',
            'products',
            'specialPrices',
        ]);

        $avatar = 'https://ui-avatars.com/api/?name='
            . urlencode($merchant->name ?? '')
            . '&background=0F172A&color=fff&size=256';
    @endphp

    <div class="space-y-6">
        <div class="cargo-card p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <img src="{{ $avatar }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-gray-200" alt="تاجر" />

                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-900">{{ $merchant->name ?? '-' }}</h2>
                            <span class="cargo-pill {{ $merchant->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $merchant->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-sm text-gray-600">
                            <span class="cargo-pill">كود التاجر: {{ $merchant->merchant_code ?? '-' }}</span>
                            <span class="cargo-pill">البريد: {{ $merchant->email ?? '-' }}</span>
                            <span class="cargo-pill">الفرع: {{ $merchant->branch?->name ?? '-' }}</span>
                        </div>

                        <div class="mt-2 text-sm text-gray-500">
                            {{ $merchant->governorate?->name ?? '-' }} - {{ $merchant->area?->name ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-2">
                    <div class="rounded-2xl bg-gray-50 p-3 text-center">
                        <div class="text-xs text-gray-500">العنوان</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $merchant->address ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-3 text-center">
                        <div class="text-xs text-gray-500">عدد المنتجات</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $merchant->products?->count() ?? 0 }}</div>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-3 text-center sm:col-span-2 lg:col-span-1">
                        <div class="text-xs text-gray-500">الأسعار الخاصة</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $merchant->specialPrices?->count() ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="cargo-card p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M16 2H8a2 2 0 0 0-2 2v16l6-3 6 3V4a2 2 0 0 0-2-2Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">الحساب</div>
                        <div class="text-sm font-bold text-gray-900">بيانات الدخول</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-700">البريد الإلكتروني: {{ $merchant->user?->email ?? '-' }}</div>
            </div>

            <div class="cargo-card p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M6 5h12v14H6z" />
                            <path d="M9 9h6M9 13h6" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">التواصل</div>
                        <div class="text-sm font-bold text-gray-900">بيانات مسؤول الحساب</div>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-sm text-gray-700">
                    <div>الاسم: {{ $merchant->contact_person_name ?? '-' }}</div>
                    <div>الهاتف: {{ $merchant->contact_person_phone ?? '-' }}</div>
                </div>
            </div>

            <div class="cargo-card p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M12 21s8-6 8-11a5 5 0 0 0-9-3 5 5 0 0 0-9 3c0 5 8 11 8 11Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">المتابعة</div>
                        <div class="text-sm font-bold text-gray-900">مسؤول المتابعة</div>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-sm text-gray-700">
                    <div>الاسم: {{ $merchant->follow_up_name ?? '-' }}</div>
                    <div>الهاتف: {{ $merchant->follow_up_phone ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="cargo-card p-6">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 1v22" />
                        <path d="M17 5H9a4 4 0 1 0 0 8h6a4 4 0 1 1 0 8H6" />
                    </svg>
                </span>
                <div>
                    <div class="text-xs text-gray-500">الأرصدة</div>
                    <div class="text-lg font-bold text-gray-900">رصيد التاجر الحالي</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="cargo-mini-card cargo-mini-card-muted">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-gray-900">الرصيد الإجمالي</div>
                        <span class="rounded-xl bg-emerald-100 px-2 py-1 text-xs text-emerald-700">متاح</span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-gray-900">
                        {{ number_format((float) ($merchant->balance ?? 0), 0) }} جنيه
                    </div>
                </div>
            </div>
        </div>

        <div class="cargo-card p-6">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M8 3h8M4 7h16M6 11h12M8 15h8M10 19h4" />
                    </svg>
                </span>
                <div>
                    <div class="text-xs text-gray-500">التسعير</div>
                    <div class="text-lg font-bold text-gray-900">رسوم التاجر</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">سعر الوزن الزائد</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $merchant->extra_weight_price ?? 0 }} جنيه</span></div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">مرتجع مدفوع</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $merchant->paid_return_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $merchant->paid_return_percent ?? 0 }}%</div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">مرتجع على الراسل</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $merchant->return_on_sender_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $merchant->return_on_sender_percent ?? 0 }}%</div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">رسوم الإلغاء</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $merchant->cancellation_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $merchant->cancellation_percent ?? 0 }}%</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
