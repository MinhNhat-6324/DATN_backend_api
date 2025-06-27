<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoaiSanPham extends Model
{
    use HasFactory;

    protected $table = 'LoaiSanPham';
    protected $primaryKey = 'id_loai';
    public $timestamps = false;

    protected $fillable = [
        'ten_loai',
    ];

    // Một loại có nhiều bài đăng
    public function baiDangs()
    {
        return $this->hasMany(BaiDang::class, 'id_loai', 'id_loai');
    }
}
