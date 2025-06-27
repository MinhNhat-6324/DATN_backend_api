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
Route::get('/tai-khoan/pending', [TaiKhoanController::class, 'danh_sach_cho']); 
Route::apiResource('tai-khoan', TaiKhoanController::class);

// Route cho chức năng đổi mật khẩu
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tai-khoan/change-password', [TaiKhoanController::class, 'changePassword']);
});


