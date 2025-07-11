<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaiKhoanController;
use App\Http\Controllers\Api\SinhVienController;
use App\Http\Controllers\Api\LoaiSanPhamController;
use App\Http\Controllers\Api\ChuyenNganhSanPhamController;
use App\Http\Controllers\Api\BaiDangController;
use App\Http\Controllers\Api\AnhBaiDangController;
use App\Http\Controllers\Api\TinNhanController;
use App\Http\Controllers\Api\BaoCaoController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ThongBaoController;
use App\Http\Controllers\Api\PasswordResetController;

// Kiểm tra đăng nhập
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

// Đăng ký với OTP
Route::post('/register/send-otp', [RegisterController::class, 'sendOtp']);
Route::post('/register/verify-otp', [RegisterController::class, 'verifyOtp']);
Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp']);
Route::post('/user/update-profile', [RegisterController::class, 'updateStudentProfile']);

// NEW: Route cho đăng ký tài khoản Admin
Route::post('/register/admin', [RegisterController::class, 'registerAdmin']);

// Route cho API chuyên ngành sản phẩm
Route::get('/chuyen-nganh-san-pham', [ChuyenNganhSanPhamController::class, 'index']);   

// Route cho API tài khoản
Route::get('/tai-khoan', [TaiKhoanController::class, 'index']); // Route lấy danh sách
Route::get('/tai-khoan/danhsach', [TaiKhoanController::class, 'danh_sach_tai_khoan']); 
Route::get('/tai-khoan/pending', [TaiKhoanController::class, 'danh_sach_cho']); 
Route::apiResource('tai-khoan', TaiKhoanController::class);
Route::get('/tai-khoan/{id}', [TaiKhoanController::class, 'show']);
Route::post('/tai-khoan/{id}/change-password', [TaiKhoanController::class, 'changePassword']);


// Routes cho Thông báo
Route::prefix('thongbao')->group(function () {
    Route::post('/tai-khoan', [ThongBaoController::class, 'guiThongBaoTaiKhoan']);
    Route::get('/nguoidung/{idTaiKhoan}', [ThongBaoController::class, 'layThongBaoTheoTaiKhoan']);
    Route::patch('/{idThongBao}', [ThongBaoController::class, 'markAsRead']); // Hoặc dùng put nếu bạn thích
});

//Bai đăng
Route::get('/bai-dang/chuyen-nganh/chung', [BaiDangController::class, 'getBaiDangChuyenNganhChung']);

Route::get('/bai-dang', [BaiDangController::class, 'index']);
Route::get('/bai-dang/nganh/{id_nganh}', [BaiDangController::class, 'getByNganh']);
Route::get('/bai-dang/tieu-de/{tieu_de}', [BaiDangController::class, 'getByTieuDe']);
Route::get('/bai-dang/nganh/{id_nganh}/loai/{id_loai}', [BaiDangController::class, 'getByNganhVaLoai']);
Route::get('/bai-dang/loc/{id_nganh}/{id_loai}/{tieu_de}', [BaiDangController::class, 'locBaiDang']);
Route::get('/bai-dang/loai/{id_loai}/tieu-de/{tieu_de}', [BaiDangController::class, 'locTheoLoaiVaTieuDe']);
Route::get('/bai-dang/loai/{id_loai}', [BaiDangController::class, 'getByLoai']);
Route::post('/bai-dang', [BaiDangController::class, 'store']);
Route::get('/bai-dang/nguoi-dung/{id_tai_khoan}', [BaiDangController::class, 'getByTaiKhoan']);
Route::get('/bai-dang/{id}', [BaiDangController::class, 'show']);
Route::put('/bai-dang/{id}', [BaiDangController::class, 'update']);
Route::delete('/bai-dang/{id}', [BaiDangController::class, 'destroy']);
Route::put('/bai-dang/{id}/doi-trang-thai', [BaiDangController::class, 'doiTrangThai']);
Route::post('bai-dang/{id}/repost', [BaiDangController::class, 'repost']);


//Ngành
Route::get('/chuyen-nganh-san-pham', [ChuyenNganhSanPhamController::class, 'index']);

//Loai
Route::get('/loai', [LoaiSanPhamController::class, 'index']);

//sinh viên 
Route::get('/sinh-vien/{id}', [SinhVienController::class, 'show']);

// báo cáo
Route::post('/bao-cao/bai-dang/{id_bai_dang}', [BaoCaoController::class, 'postByBaiDang']);
Route::get('/bao-cao', [BaoCaoController::class, 'index']);
Route::post('/bao-cao/{id}/go-bai-dang', [BaoCaoController::class, 'goBaiDang']);
Route::post('/bao-cao/{id}/tu-choi', [BaoCaoController::class, 'tuChoiBaoCao']);  


// --- Route cho chức năng Quên mật khẩu ---
Route::post('password/forgot', [PasswordResetController::class, 'sendResetOtp']);
Route::post('password/verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
Route::post('password/reset', [PasswordResetController::class, 'resetPassword']);

// tin nhắn:
Route::get('/tin-nhan/giua/{user1}/{user2}', [TinNhanController::class, 'getTinNhanGiuaHaiNguoi']);
// Route::get('/tin-nhan/danh-sach-doi-tuong/{userId}', [TinNhanController::class, 'danhSachDoiTuongChat']);
Route::get('/tin-nhan/nguoi-dung/{id}', [TinNhanController::class, 'getDanhSachTinNhanTheoNguoiDung']);

Route::post('/gui-tin-nhan', [TinNhanController::class, 'store']);
Route::put('/tin-nhan/{id}', [TinNhanController::class, 'update']);
Route::middleware('auth:sanctum')->post('/gui-email-luu-tin-nhan', [TinNhanController::class, 'sendEmailAndSave']);