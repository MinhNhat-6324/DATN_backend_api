<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnhBaiDang extends Model
{
    use HasFactory;

    // ✅ Sửa dòng này cho đúng tên bảng
    protected $table = 'anhbaidang'; // <-- chính xác tên bảng trong MySQL

    protected $primaryKey = 'id_anh';
    public $timestamps = false;

    protected $fillable = [
        'id_bai_dang',
        'duong_dan',
        'thu_tu',
    ];

    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'id_bai_dang', 'id_bai_dang');
    }
}
