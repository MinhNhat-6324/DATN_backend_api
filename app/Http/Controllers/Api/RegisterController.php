<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\TaiKhoan;
use App\Models\SinhVien;
use App\Models\OtpVerification;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Đảm bảo import Log facade

class RegisterController extends Controller
{
    /**
     * Phương thức gửi OTP và tạo tài khoản TaiKhoan tạm thời với trạng thái chờ xác thực (trang_thai = 0).
     * Trả về ID tài khoản (id_tai_khoan) để Flutter truyền sang màn hình OTP.
     */
    public function sendOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:255|unique:TaiKhoan,email|ends_with:@caothang.edu.vn',
                'ho_ten' => 'required|string|max:255',
                'mat_khau' => 'required|string|min:8|confirmed',
                'sdt' => 'nullable|string|max:20',
                'gioi_tinh' => 'nullable|integer|in:0,1',
            ]);

            $email = $request->email;

            DB::beginTransaction();

            $taiKhoan = TaiKhoan::where('email', $email)->where('trang_thai', 0)->first();

            if ($taiKhoan) {
                // Nếu tìm thấy tài khoản pending, cập nhật thông tin và xóa OTP/SinhVien cũ.
                // Để tránh lỗi duplicate key trên otp_verifications_email_unique, chúng ta sẽ dùng updateOrCreate
                // hoặc đảm bảo xóa trước khi tạo. Dòng này đang xóa tất cả OTP cho email này.
                OtpVerification::where('email', $email)->delete(); 
                SinhVien::where('id_sinh_vien', $taiKhoan->id_tai_khoan)->delete();

                $taiKhoan->update([
                    'ho_ten' => $request->ho_ten,
                    'mat_khau' => $request->mat_khau,
                    'so_dien_thoai' => $request->sdt,
                    'gioi_tinh' => $request->gioi_tinh, // Đã sửa lỗi chính tả ở đây nếu có (gioi_tinh)
                ]);
            } else {
                // Nếu không tìm thấy tài khoản pending, tạo tài khoản TaiKhoan mới
                $taiKhoan = TaiKhoan::create([
                    'email' => $email,
                    'ho_ten' => $request->ho_ten,
                    'mat_khau' => $request->mat_khau, // Lưu mật khẩu đã hash ở model nếu có setMutator
                    'so_dien_thoai' => $request->sdt,
                    'gioi_tinh' => $request->gioi_tinh,
                    'trang_thai' => 0,
                    'loai_tai_khoan' => 0,
                    'anh_dai_dien' => null,
                ]);
            }

            $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // TẠO HOẶC CẬP NHẬT BẢN GHI OTP MỚI
            // Sử dụng updateOrCreate để tránh lỗi trùng lặp trên 'email' unique key
            OtpVerification::updateOrCreate(
                ['email' => $email], // Điều kiện tìm kiếm: email
                [
                    'tai_khoan_id' => $taiKhoan->id_tai_khoan,
                    'otp_code' => $otpCode,
                    'expires_at' => now()->addMinutes(5),
                ]
            );

            Mail::to($email)->send(new OtpMail($otpCode, $email));

            DB::commit();

            return response()->json([
                'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư đến.',
                'user_id' => $taiKhoan->id_tai_khoan,
                'email' => $email,
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            // Lấy ra các lỗi validation
            $errors = $e->errors();
            
            // KIỂM TRA CHÍNH XÁC LỖI EMAIL ĐÃ TỒN TẠI
            // 'The email has already been taken.' là thông báo mặc định của Laravel cho unique rule
            if (isset($errors['email']) && in_array('The email has already been taken.', $errors['email'])) {
                return response()->json([
                    'message' => 'Email này đã được đăng ký. Vui lòng sử dụng email khác hoặc đăng nhập.',
                    'errors' => $errors,
                ], 409); // Sử dụng status 409 Conflict
            }
            
            // Nếu là lỗi validation khác
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // Ghi log lỗi chi tiết hơn
            Log::error('Error sending OTP and creating/updating pending account: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Không thể gửi mã OTP hoặc tạo/cập nhật tài khoản. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác minh OTP và kích hoạt tài khoản TaiKhoan.
     */
    public function verifyOtp(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
                'otp_code' => 'required|string|digits:6',
            ]);

            $userId = $request->user_id;
            $otpCode = $request->otp_code;

            $taiKhoan = TaiKhoan::where('id_tai_khoan', $userId)->where('trang_thai', 0)->first();

            if (!$taiKhoan) {
                 throw ValidationException::withMessages([
                    'user_id' => ['Tài khoản không tồn tại hoặc đã được xác thực.'],
                ]);
            }

            $otpRecord = OtpVerification::where('tai_khoan_id', $userId)
                                       ->where('otp_code', $otpCode)
                                       ->first();

            if (!$otpRecord) {
                throw ValidationException::withMessages([
                    'otp_code' => ['Mã OTP không hợp lệ hoặc không khớp với tài khoản này.'],
                ]);
            }

            if ($otpRecord->expires_at->isPast()) {
                $otpRecord->delete();
                throw ValidationException::withMessages([
                    'otp_code' => ['Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.'],
                ]);
            }

            DB::beginTransaction();

            $otpRecord->delete(); // Xóa OTP sau khi sử dụng thành công

            DB::commit();

            $token = $taiKhoan->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Tài khoản đã được xác thực thành công!',
                'data' => [
                    'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                    'email' => $taiKhoan->email,
                    'ho_ten' => $taiKhoan->ho_ten,
                    'is_admin' => (bool) $taiKhoan->loai_tai_khoan,
                    'token' => $token,
                ],
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error verifying OTP: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra trong quá trình xác minh. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi lại mã OTP cho tài khoản đang chờ xác thực.
     */
    public function resendOtp(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
                'email' => 'required|string|email',
            ]);

            $userId = $request->user_id;
            $email = $request->email;

            $taiKhoan = TaiKhoan::where('id_tai_khoan', $userId)
                                ->where('email', $email)
                                ->where('trang_thai', 0)
                                ->first();

            if (!$taiKhoan) {
                throw ValidationException::withMessages([
                    'user_id' => ['Tài khoản không tồn tại hoặc đã được xác thực.'],
                ]);
            }

            DB::beginTransaction();

            $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // SỬ DỤNG updateOrCreate ĐỂ TRÁNH LỖI DUPLICATE ENTRY KHI GỬI LẠI OTP
            OtpVerification::updateOrCreate(
                ['email' => $email], // Điều kiện tìm kiếm
                [
                    'tai_khoan_id' => $userId,
                    'otp_code' => $otpCode,
                    'expires_at' => now()->addMinutes(5),
                ]
            );

            Mail::to($email)->send(new OtpMail($otpCode, $email));

            DB::commit();

            return response()->json([
                'message' => 'Mã OTP mới đã được gửi đến email của bạn.',
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resending OTP: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Không thể gửi lại mã OTP. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin lớp, chuyên ngành và ảnh thẻ sinh viên cho tài khoản.
     */

     public function updateStudentProfile(Request $request)
{
    try {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
            'sinh_vien.lop' => 'required|string|max:50',
            'sinh_vien.chuyen_nganh_id' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
            'sinh_vien.anh_the_sinh_vien' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $userId = $validatedData['user_id'];
        $lop = $validatedData['sinh_vien.lop'];
        $chuyenNganhId = $validatedData['sinh_vien.chuyen_nganh_id'];
        $imageFile = $request->file('sinh_vien.anh_the_sinh_vien');

        $taiKhoan = TaiKhoan::find($userId);
        if (!$taiKhoan) {
            return response()->json(['message' => 'Không tìm thấy tài khoản để cập nhật.'], 404);
        }

        DB::beginTransaction();

        $publicImageUrl = null;
        if ($imageFile) {
            $imageName = $userId . '_student_card_' . time() . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = $imageFile->storeAs('student_cards', $imageName, 'public');
            $publicImageUrl = Storage::url($imagePath);
        }

        $sinhVien = SinhVien::find($userId);
        if ($sinhVien) {
            if ($imageFile && $sinhVien->anh_the_sinh_vien) {
                $oldImagePath = str_replace(Storage::url(''), '', $sinhVien->anh_the_sinh_vien);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $sinhVien->update([
                'lop' => $lop,
                'id_nganh' => $chuyenNganhId,
                'anh_the_sinh_vien' => $publicImageUrl ?? $sinhVien->anh_the_sinh_vien,
            ]);
        } else {
            SinhVien::create([
                'id_sinh_vien' => $userId,
                'lop' => $lop,
                'id_nganh' => $chuyenNganhId,
                'anh_the_sinh_vien' => $publicImageUrl,
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'Thông tin sinh viên đã được cập nhật thành công!',
        ], 200);

    } catch (ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Dữ liệu đầu vào không hợp lệ.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Đã có lỗi xảy ra khi cập nhật thông tin sinh viên.',
            'error_detail' => $e->getMessage(),
        ], 500);
    }
}

     /**
     * Phương thức đăng ký tài khoản Admin.
     * Chỉ yêu cầu thông tin TaiKhoan, không cần OTP, loai_tai_khoan = 1, trang_thai = 1.
     */
      public function registerAdmin(Request $request)
    {
        try {
            // 1. Validation: Đảm bảo dữ liệu đầu vào hợp lệ cho tài khoản admin
            $request->validate([
                'email' => 'required|string|email|max:255|unique:TaiKhoan,email|ends_with:@caothang.edu.vn', // Ràng buộc domain email
                'ho_ten' => 'required|string|max:255',
                'mat_khau' => 'required|string|min:8|confirmed',
                'sdt' => 'nullable|string|max:20',
                'gioi_tinh' => 'nullable|integer|in:0,1',
            ]);

            DB::beginTransaction(); // Bắt đầu giao dịch cơ sở dữ liệu

            // 2. Tạo tài khoản TaiKhoan mới với loai_tai_khoan = 1 và trang_thai = 1
            $taiKhoan = TaiKhoan::create([
                'email' => $request->email,
                'ho_ten' => $request->ho_ten,
                'mat_khau' => Hash::make($request->mat_khau), // Băm mật khẩu trước khi lưu
                'so_dien_thoai' => $request->sdt,
                'gioi_tinh' => $request->gioi_tinh,
                'trang_thai' => 1, // Kích hoạt ngay lập tức cho admin
                'loai_tai_khoan' => 1, // Đặt là tài khoản admin
                'anh_dai_dien' => null, // Admin có thể cập nhật sau nếu cần
            ]);

            DB::commit(); // Hoàn tất giao dịch

            // 3. Trả về phản hồi thành công
            return response()->json([
                'message' => 'Tài khoản quản trị đã được đăng ký thành công!',
                'data' => [
                    'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                    'email' => $taiKhoan->email,
                    'ho_ten' => $taiKhoan->ho_ten,
                    'loai_tai_khoan' => $taiKhoan->loai_tai_khoan,
                ],
            ], 201); // Sử dụng status 201 Created

        } catch (ValidationException $e) {
            DB::rollBack(); // Hoàn tác nếu có lỗi validation
            $errors = $e->errors();

            // KIỂM TRA CHÍNH XÁC LỖI EMAIL ĐÃ TỒN TẠI
            // 'The email has already been taken.' là thông báo mặc định của Laravel cho unique rule
            if (isset($errors['email']) && in_array('The email has already been taken.', $errors['email'])) {
                return response()->json([
                    'message' => 'Email này đã được đăng ký cho một tài khoản khác. Vui lòng sử dụng email khác.',
                    'errors' => $errors,
                ], 409); // Sử dụng status 409 Conflict
            }
            
            // Nếu là lỗi validation khác
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Hoàn tác nếu có lỗi hệ thống
            Log::error('Error registering admin account: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Không thể đăng ký tài khoản quản trị. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
}
}
