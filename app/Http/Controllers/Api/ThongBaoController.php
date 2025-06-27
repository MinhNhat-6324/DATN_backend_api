<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\ThongBao;

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
            1 => 'Tài khoản của bạn đã được duyệt.',
            2 => 'Tài khoản của bạn đã bị khóa.',
            0 => 'Tài khoản của bạn đã được mở khóa.',
            default => null
        };

        if ($noiDung) {
            // Ghi trực tiếp vào DB
            ThongBao::create([
                'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                'noi_dung' => $noiDung,
                'thoi_gian_tao' => now(),
                'da_doc' => 0
            ]);

            return response()->json(['message' => 'Gửi thông báo thành công']);
        }

        return response()->json(['message' => 'Trạng thái không hợp lệ'], 400);
    }

    // Lấy danh sách thông báo của người dùng
    public function layThongBaoTheoTaiKhoan($idTaiKhoan)
    {
        $thongBao = ThongBao::where('id_tai_khoan', $idTaiKhoan)
                            ->orderByDesc('thoi_gian_tao')
                            ->get();

        return response()->json($thongBao);
    }
}
