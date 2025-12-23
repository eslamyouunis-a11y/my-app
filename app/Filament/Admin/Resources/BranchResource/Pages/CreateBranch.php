<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function afterCreate(): void
    {
        $branch = $this->record;
        $data = $this->data;

        $user = User::create([
            'name'     => $branch->name,
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('branch');

        if (Schema::hasColumn('users', 'branch_id')) {
            $user->branch_id = $branch->id;
            $user->save();
        }
    }
}
