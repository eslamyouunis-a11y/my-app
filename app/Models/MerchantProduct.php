<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MerchantProduct extends Model
{
    protected $fillable = ['merchant_id', 'name', 'sku', 'default_weight'];
}
