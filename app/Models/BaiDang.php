<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaiDang extends Model
{
    use HasFactory;

    protected $table = 'BaiDang';
    protected $primaryKey = 'id_bai_dang';
    public $timestamps = false;

    protected $fillable = [
        'id_tai_khoan',
        'tieu_de',
        'do_moi',
        'id_loai',
        'id_nganh',
        'gia',
        'ngay_dang',
        'trang_thai',
    ];

    // Mối quan hệ: Một BaiDang thuộc về một TaiKhoan
    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'id_tai_khoan', 'id_tai_khoan');
    }

    // Mối quan hệ: Một BaiDang thuộc về một LoaiSanPham
    public function loaiSanPham()
    {
        return $this->belongsTo(LoaiSanPham::class, 'id_loai', 'id_loai');
    }

    // Mối quan hệ: Một BaiDang thuộc về một ChuyenNganhSanPham
    public function chuyenNganhSanPham()
    {
        return $this->belongsTo(ChuyenNganhSanPham::class, 'id_nganh', 'id_nganh');
    }

    // Mối quan hệ: Một BaiDang có nhiều AnhBaiDang
    public function anhBaiDangs()
    {
        return $this->hasMany(AnhBaiDang::class, 'id_bai_dang', 'id_bai_dang');
    }

    // Mối quan hệ: Một BaiDang có nhiều TinNhan liên quan
    public function tinNhans()
    {
        return $this->hasMany(TinNhan::class, 'bai_dang_lien_quan', 'id_bai_dang');
    }

    // Mối quan hệ: Một BaiDang có nhiều BaoCao
    public function baoCaos()
    {
        return $this->hasMany(BaoCao::class, 'ma_bai_dang', 'id_bai_dang');
    }
}