<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\OtpMail; // Đảm bảo đúng namespace của OtpMail của bạn

class PasswordResetController extends Controller
{
    /**
     * Yêu cầu đặt lại mật khẩu - Gửi mã OTP đến email.
     * POST /api/password/forgot
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
            ], [
                'email.required' => 'Vui lòng nhập địa chỉ email.',
                'email.email' => 'Địa chỉ email không hợp lệ.',
            ]);

            // Tìm tài khoản và kiểm tra trạng thái
            $taiKhoan = TaiKhoan::where('email', $request->email)
                                ->where('trang_thai', 1) // Chỉ cho phép đặt lại mật khẩu cho tài khoản đang hoạt động
                                ->first();

            if (!$taiKhoan) {
                // Trả về thông báo chung để tránh tiết lộ tài khoản nào tồn tại
                return response()->json([
                    'message' => 'Email này chưa được đăng ký hoặc tài khoản của bạn chưa được kích hoạt/đã bị khóa.'
                ], 404);
            }

            DB::beginTransaction();

            // Tạo mã OTP 6 chữ số
            $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Lưu hoặc cập nhật OTP vào bảng otp_verifications với type 'password_reset'
            OtpVerification::updateOrCreate(
                ['email' => $request->email, 'type' => 'password_reset'],
                [
                    'tai_khoan_id' => $taiKhoan->id_tai_khoan,
                    'otp_code' => $otpCode,
                    'expires_at' => now()->addMinutes(5), // OTP hết hạn sau 5 phút
                ]
            );

            // Gửi email chứa OTP
            // Bạn có thể cần truyền thêm tham số vào OtpMail constructor nếu muốn nội dung email khác biệt
            // Ví dụ: new OtpMail($otpCode, $request->email, 'password_reset')
            Mail::to($request->email)->send(new OtpMail($otpCode, $request->email, 'password_reset'));

            DB::commit();

            return response()->json([
                'message' => 'Mã OTP để đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư đến.'
            ], 200);

        } catch (ValidationException $e) {
            Log::warning('Validation error during password reset OTP request: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error sending password reset OTP: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi gửi mã OTP. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực mã OTP để cho phép đặt lại mật khẩu.
     * POST /api/password/verify-reset-otp
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'otp_code' => 'required|string|digits:6',
            ], [
                'email.required' => 'Vui lòng cung cấp email.',
                'otp_code.required' => 'Vui lòng nhập mã OTP.',
                'otp_code.digits' => 'Mã OTP phải có 6 chữ số.',
            ]);

            $otpRecord = OtpVerification::where('email', $request->email)
                                        ->where('otp_code', $request->otp_code)
                                        ->where('type', 'password_reset') // Đảm bảo đúng loại OTP
                                        ->first();

            if (!$otpRecord) {
                throw ValidationException::withMessages(['otp_code' => 'Mã OTP không hợp lệ hoặc không đúng với email này.']);
            }

            if ($otpRecord->expires_at->isPast()) {
                $otpRecord->delete(); // Xóa OTP hết hạn
                throw ValidationException::withMessages(['otp_code' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
            }

            return response()->json([
                'message' => 'Mã OTP hợp lệ. Bạn có thể đặt lại mật khẩu mới.'
            ], 200);

        } catch (ValidationException $e) {
            Log::warning('Validation error during password reset OTP verification: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error verifying password reset OTP: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đặt lại mật khẩu mới sau khi xác thực OTP thành công.
     * POST /api/password/reset
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'otp_code' => 'required|string|digits:6',
                'new_password' => 'required|string|min:8|confirmed', // 'confirmed' kiểm tra new_password_confirmation
            ], [
                'email.required' => 'Vui lòng cung cấp email.',
                'otp_code.required' => 'Vui lòng nhập mã OTP.',
                'otp_code.digits' => 'Mã OTP phải có 6 chữ số.',
                'new_password.required' => 'Vui lòng nhập mật khẩu mới.',
                'new_password.min' => 'Mật khẩu mới phải có ít nhất :min ký tự.',
                'new_password.confirmed' => 'Mật khẩu mới và xác nhận mật khẩu không khớp.',
            ]);

            DB::beginTransaction();

            // Xác thực lại OTP một lần nữa để đảm bảo chưa bị sử dụng hoặc hết hạn
            $otpRecord = OtpVerification::where('email', $request->email)
                                        ->where('otp_code', $request->otp_code)
                                        ->where('type', 'password_reset')
                                        ->first();

            if (!$otpRecord) {
                DB::rollBack();
                throw ValidationException::withMessages(['otp_code' => 'Mã OTP không hợp lệ hoặc đã được sử dụng.']);
            }

            if ($otpRecord->expires_at->isPast()) {
                $otpRecord->delete(); // Xóa OTP hết hạn
                DB::rollBack();
                throw ValidationException::withMessages(['otp_code' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.']);
            }

            // Tìm tài khoản
            $taiKhoan = TaiKhoan::where('id_tai_khoan', $otpRecord->tai_khoan_id)->first();
            // Hoặc nếu bạn muốn tìm theo email một lần nữa để chắc chắn:
            // $taiKhoan = TaiKhoan::where('email', $request->email)->first();

            if (!$taiKhoan) {
                DB::rollBack();
                return response()->json(['message' => 'Không tìm thấy tài khoản để đặt lại mật khẩu.'], 404);
            }

            // Cập nhật mật khẩu mới
            $taiKhoan->mat_khau = Hash::make($request->new_password);
            $taiKhoan->save();

            // Xóa mã OTP sau khi sử dụng thành công
            $otpRecord->delete();

            DB::commit();

            return response()->json([
                'message' => 'Mật khẩu của bạn đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.'
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation error during password reset: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting password: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi đặt lại mật khẩu. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}