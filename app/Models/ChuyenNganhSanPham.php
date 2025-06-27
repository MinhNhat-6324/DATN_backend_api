<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChuyenNganhSanPham extends Model
{
    use HasFactory;

    protected $table = 'ChuyenNganhSanPham';
    protected $primaryKey = 'id_nganh';
    public $timestamps = false;

    protected $fillable = [
        'ten_nganh',
    ];

    // Một ChuyenNganhSanPham có thể có nhiều BaiDang.
    public function baiDang()
    {
        return $this->hasMany(BaiDang::class, 'id_nganh', 'id_nganh');
    }
    
    // Một ChuyenNganhSanPham có nhiều SinhVien
    public function sinhViens()
    {
       
        return $this->hasMany(SinhVien::class, 'id_nganh', 'id_nganh');
    }
}