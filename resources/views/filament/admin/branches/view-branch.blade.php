<x-filament-panels::page>
    @php
        $branch = $record->loadMissing([
            'governorate',
            'areas',
            'couriers',
            'user',
        ]);

        $branchTypes = \App\Models\Branch::branchTypes();
        $branchTypeLabel = $branchTypes[$branch->branch_type] ?? $branch->branch_type;

        $avatar = 'https://ui-avatars.com/api/?name='
            . urlencode($branch->name ?? '')
            . '&background=0F172A&color=fff&size=256';
    @endphp

    <div class="space-y-6">
        <div class="cargo-card p-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <img src="{{ $avatar }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-gray-200" alt="فرع" />

                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-bold text-gray-900">{{ $branch->name ?? '-' }}</h2>
                            <span class="cargo-pill {{ $branch->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $branch->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-sm text-gray-600">
                            <span class="cargo-pill">رقم الفرع: {{ $branch->branch_number ?? '-' }}</span>
                            <span class="cargo-pill">نوع الفرع: {{ $branchTypeLabel ?? '-' }}</span>
                            <span class="cargo-pill">المحافظة: {{ $branch->governorate?->name ?? '-' }}</span>
                        </div>

                        <div class="mt-2 text-sm text-gray-500">{{ $branch->address ?? '-' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-2">
                    <div class="rounded-2xl bg-gray-50 p-3 text-center">
                        <div class="text-xs text-gray-500">مدير الفرع</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $branch->manager_name ?? '-' }}</div>
                        <div class="mt-1 text-xs text-gray-500">{{ $branch->manager_phone ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-3 text-center">
                        <div class="text-xs text-gray-500">البريد</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $branch->email ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-3 text-center sm:col-span-2 lg:col-span-1">
                        <div class="text-xs text-gray-500">عدد المناديب</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $branch->couriers?->count() ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="cargo-card p-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M16 2H8a2 2 0 0 0-2 2v16l6-3 6 3V4a2 2 0 0 0-2-2Z" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-xs text-gray-500">التغطية</div>
                        <div class="text-sm font-bold text-gray-900">المناطق التابعة</div>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-sm text-gray-700">
                    @forelse ($branch->areas ?? [] as $area)
                        <span class="rounded-full bg-gray-100 px-3 py-1">{{ $area->name }}</span>
                    @empty
                        <span class="text-gray-500">لا توجد مناطق مرتبطة</span>
                    @endforelse
                </div>
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
                        <div class="text-xs text-gray-500">الحساب</div>
                        <div class="text-sm font-bold text-gray-900">بيانات دخول الفرع</div>
                    </div>
                </div>
                <div class="mt-4 text-sm text-gray-700">
                    البريد الإلكتروني: {{ $branch->user?->email ?? '-' }}
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
                    <div class="text-lg font-bold text-gray-900">أرصدة الفرع الحالية</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="cargo-mini-card cargo-mini-card-muted">
                    <div class="text-sm font-semibold text-gray-900">الرصيد الإجمالي</div>
                    <div class="mt-2 text-xl font-bold text-gray-900">
                        {{ number_format((float) ($branch->total_balance ?? 0), 0) }} جنيه
                    </div>
                </div>

                <div class="cargo-mini-card cargo-mini-card-muted">
                    <div class="text-sm font-semibold text-gray-900">رصيد العمولة</div>
                    <div class="mt-2 text-xl font-bold text-gray-900">
                        {{ number_format((float) ($branch->commission_balance ?? 0), 0) }} جنيه
                    </div>
                </div>

                <div class="cargo-mini-card">
                    <div class="text-sm font-semibold text-gray-900">عهدة المناديب</div>
                    <div class="mt-2 text-xl font-bold text-gray-900">
                        {{ number_format((float) ($branch->couriers_custody_balance ?? 0), 0) }} جنيه
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
                    <div class="text-lg font-bold text-gray-900">رسوم الفرع الافتراضية</div>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">تسليم عادي</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $branch->normal_delivery_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $branch->normal_delivery_percent ?? 0 }}%</div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">مرتجع مدفوع</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $branch->normal_paid_return_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $branch->normal_paid_return_percent ?? 0 }}%</div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">مرتجع على الراسل</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $branch->normal_return_on_sender_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $branch->normal_return_on_sender_percent ?? 0 }}%</div>
                </div>

                <div class="rounded-2xl bg-gray-50 p-4">
                    <div class="text-sm font-semibold text-gray-900">تسليم مكتب</div>
                    <div class="mt-2 text-sm text-gray-700">قيمة: <span class="font-semibold">{{ $branch->office_delivery_fee ?? 0 }} جنيه</span></div>
                    <div class="text-sm text-gray-700">نسبة: {{ $branch->office_delivery_percent ?? 0 }}%</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
