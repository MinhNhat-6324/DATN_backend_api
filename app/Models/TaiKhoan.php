<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash; // Import Hash Facade

class TaiKhoan extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'TaiKhoan'; // Đảm bảo đúng tên bảng
    protected $primaryKey = 'id_tai_khoan'; // Đảm bảo đúng khóa chính

    
    // Các trường có thể được gán hàng loạt (mass assignable)
    protected $fillable = [
        'email',
        'ho_ten',
        'gioi_tinh',
        'mat_khau',
        'anh_dai_dien', // Giữ lại nếu bạn có kế hoạch cập nhật nó sau này
        'so_dien_thoai',
        'trang_thai',
        'loai_tai_khoan',
    ];

    // Các trường nên bị ẩn khỏi mảng khi serialize
    protected $hidden = [
        'mat_khau',
        'remember_token',
    ];

    // Các trường nên được chuyển đổi sang kiểu dữ liệu native
    protected $casts = [
        'mat_khau' => 'hashed', // Laravel sẽ tự động hash mật khẩu khi gán
        'gioi_tinh' => 'integer',
        'trang_thai' => 'integer',
        'loai_tai_khoan' => 'integer',
    ];

    /**
     * Định nghĩa quan hệ 1-1 với bảng SinhVien.
     */
    public function sinhVien()
    {
        return $this->hasOne(SinhVien::class, 'id_sinh_vien', 'id_tai_khoan');
    }
}