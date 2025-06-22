<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TinNhan extends Model
{
    use HasFactory;

    protected $table = 'TinNhan';
    protected $primaryKey = 'id_tin_nhan';
    public $timestamps = false;

    protected $fillable = [
        'nguoi_gui',
        'nguoi_nhan',
        'bai_dang_lien_quan',
        'noi_dung',
        'thoi_gian_gui',
    ];

    // Một TinNhan được gửi bởi một TaiKhoan.
    public function nguoiGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoi_gui', 'id_tai_khoan');
    }

    // Một TinNhan được gửi tới một TaiKhoan.
    public function nguoiNhan()
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoi_nhan', 'id_tai_khoan');
    }

    // Một TinNhan có thể liên quan đến một BaiDang (có thể là null).
    public function baiDangLienQuan()
    {
        return $this->belongsTo(BaiDang::class, 'bai_dang_lien_quan', 'id_bai_dang');
    }
}