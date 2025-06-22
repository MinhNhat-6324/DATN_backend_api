<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications'; // Hoặc 'otp_verifications' tùy theo tên bảng của bạn
    protected $primaryKey = 'id'; // Khóa chính mặc định là 'id' và tự động tăng
    public $incrementing = true; // Mặc định là true, nhưng nên rõ ràng
    protected $keyType = 'int'; // Kiểu của khóa chính

    protected $fillable = [
        'tai_khoan_id',
        'email',
        'otp_code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Nếu bảng có timestamps, hãy giữ dòng này (mặc định là true)
    // public $timestamps = true; 
    // Nếu bảng KHÔNG CÓ timestamps, hãy thêm dòng này
    // public $timestamps = false; 

    // Quan hệ với TaiKhoan (nếu cần)
    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id', 'id_tai_khoan');
    }
}
