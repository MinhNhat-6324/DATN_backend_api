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
                OtpVerification::where('email', $email)->delete(); 
                SinhVien::where('id_sinh_vien', $taiKhoan->id_tai_khoan)->delete();

                $taiKhoan->update([
                    'ho_ten' => $request->ho_ten,
                    'mat_khau' => $request->mat_khau,
                    'so_dien_thoai' => $request->sdt,
                    'gioi_tinh' => $request->gioi_tinh,
                ]);
            } else {
                // Nếu không tìm thấy tài khoản pending, tạo tài khoản TaiKhoan mới
                $taiKhoan = TaiKhoan::create([
                    'email' => $email,
                    'ho_ten' => $request->ho_ten,
                    'mat_khau' => $request->mat_khau,
                    'so_dien_thoai' => $request->sdt,
                    'gioi_tinh' => $request->gioi_tinh,
                    'trang_thai' => 0,
                    'loai_tai_khoan' => 0,
                    'anh_dai_dien' => null,
                ]);
            }

            $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // TẠO BẢN GHI OTP MỚI
            OtpVerification::create([
                'tai_khoan_id' => $taiKhoan->id_tai_khoan,
                'email' => $email,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
            ]);

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
            
            // Kiểm tra nếu lỗi là do email đã tồn tại
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
            \Log::error('Error sending OTP and creating/updating pending account: ' . $e->getMessage(), ['exception' => $e]);
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

            $taiKhoan->update([
                'trang_thai' => 1,
            ]);

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
            \Log::error('Error verifying OTP: ' . $e->getMessage(), ['exception' => $e]);
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

            // XÓA OTP CŨ TRƯỚC KHI TẠO MỚI CHO YÊU CẦU GỬI LẠI
            OtpVerification::where('email', $email)->delete(); 

            $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            OtpVerification::create([
                'tai_khoan_id' => $userId,
                'email' => $email,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
            ]);

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
            \Log::error('Error resending OTP: ' . $e->getMessage(), ['exception' => $e]);
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
            $request->validate([
                'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
                'lop' => 'required|string|max:50',
                'chuyen_nganh' => 'required|string|max:100',
                'anh_dai_dien' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $userId = $request->user_id;
            $lop = $request->lop;
            $chuyenNganh = $request->chuyen_nganh;
            $imageFile = $request->file('anh_dai_dien');

            $taiKhoan = TaiKhoan::find($userId);

            if (!$taiKhoan) {
                return response()->json(['message' => 'Không tìm thấy tài khoản để cập nhật.'], 404);
            }

            if ($taiKhoan->trang_thai !== 1) {
                return response()->json(['message' => 'Tài khoản chưa được xác thực. Vui lòng hoàn tất xác thực OTP trước.'], 403);
            }

            DB::beginTransaction();

            $imageName = $userId . '_student_card_' . time() . '.' . $imageFile->getClientOriginalExtension();
            $imagePath = $imageFile->storeAs('public/student_cards', $imageName);
            $publicImageUrl = Storage::url($imagePath);

            $sinhVien = SinhVien::find($userId);

            if ($sinhVien) {
                if ($sinhVien->anh_the_sinh_vien && Storage::disk('public')->exists(str_replace('storage/', '', $sinhVien->anh_the_sinh_vien))) {
                     Storage::disk('public')->delete(str_replace('storage/', '', $sinhVien->anh_the_sinh_vien));
                }

                $sinhVien->update([
                    'lop' => $lop,
                    'chuyen_nganh' => $chuyenNganh,
                    'anh_the_sinh_vien' => $publicImageUrl,
                ]);
            } else {
                SinhVien::create([
                    'id_sinh_vien' => $userId,
                    'lop' => $lop,
                    'chuyen_nganh' => $chuyenNganh,
                    'anh_the_sinh_vien' => $publicImageUrl,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Thông tin sinh viên đã được cập nhật thành công!',
                'data' => [
                    'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                    'email' => $taiKhoan->email,
                    'lop' => $lop,
                    'chuyen_nganh' => $chuyenNganh,
                    'anh_the_sinh_vien_url' => $publicImageUrl,
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating student profile: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật thông tin sinh viên. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}

