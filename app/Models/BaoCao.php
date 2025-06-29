<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaoCao extends Model
{
    use HasFactory;

    protected $table = 'BaoCao';
    protected $primaryKey = 'id_bao_cao';
    public $timestamps = false;

    protected $fillable = [
        'ma_bai_dang',
        'id_tai_khoan_bao_cao',
        'ly_do',
        'mo_ta_them',
        'thoi_gian_bao_cao',
        'trang_thai',
    ];

    // Một báo cáo thuộc về một bài đăng
    public function baiDang()
    {
        return $this->belongsTo(BaiDang::class, 'ma_bai_dang', 'id_bai_dang');
    }

    // Một báo cáo được tạo bởi một tài khoản
    public function taiKhoanBaoCao()
    {
        return $this->belongsTo(TaiKhoan::class, 'id_tai_khoan_bao_cao', 'id_tai_khoan');
    }
}
