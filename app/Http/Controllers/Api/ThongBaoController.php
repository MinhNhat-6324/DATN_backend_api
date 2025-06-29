<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\ThongBao; // Đảm bảo đã import model ThongBao

class ThongBaoController extends Controller
{
    // Gửi thông báo trạng thái tài khoản
    public function guiThongBaoTaiKhoan(Request $request)
    {
        $request->validate([
            'id_tai_khoan' => 'required|exists:TaiKhoan,id_tai_khoan',
            'trang_thai' => 'required|integer',
        ]);

        $taiKhoan = TaiKhoan::findOrFail($request->id_tai_khoan);

        $noiDung = match ($request->trang_thai) {
            1 => 'Tài khoản của bạn đã có thể hoạt động.',
            2 => 'Tài khoản của bạn đã bị khóa vì lí do chính sách.',
            default => null
        };

        if ($noiDung) {
            // Tạo thông báo với noi_dung và da_doc mặc định là 0
            ThongBao::create([
                'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                'noi_dung' => $noiDung,
                'thoi_gian_tao' => now(),
                'da_doc' => 0 // Mặc định là chưa đọc
            ]);

            return response()->json(['message' => 'Gửi thông báo thành công']);
        }

        return response()->json(['message' => 'Trạng thái không hợp lệ'], 400);
    }

    // Lấy danh sách thông báo của người dùng
    public function layThongBaoTheoTaiKhoan($idTaiKhoan)
        {
            try {
                // Lấy thông báo theo idTaiKhoan và sắp xếp theo 'thoi_gian_tao' giảm dần
                // Hoặc bạn có thể sắp xếp theo 'id_thong_bao' giảm dần nếu nó luôn tăng
                $thongBaos = ThongBao::where('id_tai_khoan', $idTaiKhoan)
                                    ->orderBy('id_thong_bao', 'desc')
                                    ->get();

                return response()->json($thongBaos);

            } catch (\Exception $e) {
                // Ghi log lỗi để dễ dàng debug
                \Log::error('Lỗi khi lấy thông báo theo tài khoản: ' . $e->getMessage());
                return response()->json(['message' => 'Không thể lấy danh sách thông báo.', 'error' => $e->getMessage()], 500);
            }
        }

    // ĐÁNH DẤU THÔNG BÁO ĐÃ ĐỌC
    public function markAsRead(Request $request, $idThongBao)
    {
        $thongBao = ThongBao::find($idThongBao);

        if (!$thongBao) {
            return response()->json(['message' => 'Thông báo không tồn tại'], 404);
        }

        // Cập nhật trường da_doc thành 1 (đã đọc)
        // Lấy giá trị từ request, mặc định là 1 nếu không gửi
        $thongBao->da_doc = $request->input('da_doc', 1);

        $thongBao->save();

        return response()->json(['message' => 'Cập nhật trạng thái thông báo thành công', 'thong_bao' => $thongBao]);
    }
}