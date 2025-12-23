<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function afterSave(): void
    {
        $branch = $this->record;
        $data = $this->data;

        // لو جدول users فيه branch_id
        if (! Schema::hasColumn('users', 'branch_id')) {
            return;
        }

        // user المرتبط بالفرع
        $user = $branch->user;

        if (! $user) {
            return;
        }

        // ✅ sync email
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $user->email = $data['email'];
        }

        // ✅ sync password لو اتكتب
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
