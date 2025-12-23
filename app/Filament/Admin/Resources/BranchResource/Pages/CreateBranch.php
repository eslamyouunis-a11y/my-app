<?php

namespace App\Filament\Admin\Resources\BranchResource\Pages;

use App\Filament\Admin\Resources\BranchResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // 1. استخراج بيانات المستخدم (بما فيها الباسورد اللي بقة موجود دلوقتي)
            $userData = [
                'name'     => $data['manager_name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ];

            // 2. ⚠️ خطوة مهمة جداً: حذف الباسورد من الداتا عشان الفرع ميعملش Error
            // لأن جدول branches مفيهوش عمود اسمه password
            unset($data['password']);

            // 3. إنشاء الفرع (بدون الباسورد)
            $branch = static::getModel()::create($data);

            // 4. إنشاء المستخدم وربطه بالفرع
            $user = User::create([
                'name'      => $userData['name'],
                'email'     => $userData['email'],
                'password'  => Hash::make($userData['password']),
                'branch_id' => $branch->id,
            ]);

            // 5. تعيين الصلاحية
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('branch');
            }

            return $branch;
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم إنشاء الفرع بنجاح')
            ->body('تم إنشاء الفرع وحساب المدير المرتبط به.');
    }
}
