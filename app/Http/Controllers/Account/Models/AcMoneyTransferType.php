<?php

namespace App\Http\Controllers\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcMoneyTransferType extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'ac_moneytransfer_types';

    public function creator_info()
    {
        return $this->belongsTo(User::class, 'creator');
    }
}
