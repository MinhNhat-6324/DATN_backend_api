<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table = 'thongbao';
    protected $primaryKey = 'id_thong_bao';
    public $timestamps = false; // Vì bạn dùng trường `thoi_gian_tao` thay vì created_at

    protected $fillable = [
        'id_tai_khoan',
        'noi_dung',
        'thoi_gian_tao',
        'da_doc',
    ];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'id_tai_khoan', 'id_tai_khoan');
    }
}
