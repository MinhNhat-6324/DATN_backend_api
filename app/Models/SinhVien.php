<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinhVien extends Model
{
    use HasFactory;

    protected $table = 'SinhVien'; // Đảm bảo đúng tên bảng
    protected $primaryKey = 'id_sinh_vien'; // Khóa chính là id_sinh_vien

    // KHÔNG TỰ ĐỘNG TĂNG: id_sinh_vien nhận giá trị từ id_tai_khoan
    public $incrementing = false; 

    // Kiểu dữ liệu của khóa chính
    protected $keyType = 'int'; 

    // Không sử dụng timestamps nếu bảng không có created_at và updated_at
    public $timestamps = false; 

    protected $fillable = [
        'id_sinh_vien', // Cần cho phép gán id_sinh_vien vì bạn sẽ gán giá trị thủ công
        'anh_the_sinh_vien',
        'lop',
        'id_nganh',
    ];

    /**
     * Định nghĩa quan hệ 1-1 với bảng TaiKhoan (ngược lại với TaiKhoan->sinhVien).
     * Một SinhVien thuộc về một TaiKhoan.
     */
    public function taiKhoan()
    {
        // 'id_sinh_vien' là foreign key trên bảng SinhVien (local key)
        // 'id_tai_khoan' là primary key trên bảng TaiKhoan (owner key)
        return $this->belongsTo(TaiKhoan::class, 'id_sinh_vien', 'id_tai_khoan');
    }

    public function chuyenNganhSanPham() // Đổi tên hàm cho rõ ràng hơn (ChuyenNganhSanpham)
    {
        return $this->belongsTo(ChuyenNganhSanpham::class, 'id_nganh', 'id_nganh');
    }
}
