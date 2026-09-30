<?php

use App\Models\Branch;
use App\Models\Governorate;
use App\Models\Merchant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('persists merchants created in the same second with distinct codes and preserves legacy codes', function () {
    $this->freezeTime();
    $timestamp = now()->timestamp;

    $governorate = Governorate::create(['name' => 'Test Governorate']);
    $branch = Branch::create([
        'name' => 'Test Branch',
        'branch_type' => 'direct',
        'governorate_id' => $governorate->id,
        'manager_name' => 'Manager',
        'manager_phone' => '01000000000',
    ]);
    $attributes = [
        'name' => 'Test Merchant',
        'branch_id' => $branch->id,
        'contact_person_name' => 'Contact',
        'contact_person_phone' => '01011111111',
    ];

    // Existing records used the timestamp-only format.
    $legacyCode = 'M-' . $timestamp;
    $legacyId = DB::table('merchants')->insertGetId([
        ...$attributes,
        'merchant_code' => $legacyCode,
    ]);

    $first = Merchant::create($attributes);
    $second = Merchant::create($attributes);

    expect($first->created_at->timestamp)->toBe($timestamp)
        ->and($second->created_at->timestamp)->toBe($timestamp)
        ->and($first->merchant_code)->not->toBe($second->merchant_code);

    foreach ([$first, $second] as $merchant) {
        expect($merchant->merchant_code)->toStartWith('M-' . $timestamp . '-')
            ->and(Str::isUuid(Str::after($merchant->merchant_code, 'M-' . $timestamp . '-')))->toBeTrue();

        $this->assertDatabaseHas('merchants', [
            'id' => $merchant->id,
            'merchant_code' => $merchant->merchant_code,
        ]);
    }

    $legacy = Merchant::findOrFail($legacyId);
    $legacy->update(['name' => 'Updated Merchant']);

    expect($legacy->fresh()->merchant_code)->toBe($legacyCode);
    $this->assertDatabaseCount('merchants', 3);
});
