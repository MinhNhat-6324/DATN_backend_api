<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\TaiKhoan;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // 1. Xác thực dữ liệu đầu vào với quy tắc chặt chẽ hơn và thông báo tùy chỉnh
        try {
            $request->validate([
                'email' => 'required|string|email', // Thêm kiểm tra định dạng email
                'password' => 'required|string|min:8', // Đảm bảo mật khẩu tối thiểu 8 ký tự
            ], [
                'email.required' => 'Email không được để trống.',
                'email.string' => 'Email phải là chuỗi.',
                'email.email' => 'Email không đúng định dạng.',
                'password.required' => 'Mật khẩu không được để trống.',
                'password.string' => 'Mật khẩu phải là chuỗi.',
                'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        }

        // 2. Lấy thông tin tài khoản từ cơ sở dữ liệu
        $taiKhoan = TaiKhoan::where('email', $request->email)->first();

        // 3. Kiểm tra tài khoản có tồn tại và mật khẩu có khớp không
        if (!$taiKhoan || !Hash::check($request->password, $taiKhoan->mat_khau)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        // 4. Nếu xác thực thành công:
        $token = $taiKhoan->createToken('auth_token')->plainTextToken;

        // 5. Phản hồi thành công
        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                'email' => $taiKhoan->email, 
                'loai_tai_khoan' => $taiKhoan->loai_tai_khoan, 
                'token' => $token,
            ],
        ], 200); // Mã trạng thái 200: OK
    }
}