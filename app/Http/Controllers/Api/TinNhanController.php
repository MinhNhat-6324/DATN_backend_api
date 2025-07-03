<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TinNhan; // Import Model TinNhan
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TinNhanController extends Controller
{
    /**
     * Lấy danh sách tất cả tin nhắn.
     * GET /api/tin-nhan
     */
    public function index()
    {
        $tinNhans = TinNhan::with(['nguoiGui', 'nguoiNhan', 'baiDangLienQuan'])->get();
        return response()->json($tinNhans);
    }

    /**
     * Tạo một tin nhắn mới.
     * POST /api/tin-nhan
     */
    public function store(Request $request)
{
    try {
        $request->validate([
            'nguoi_gui' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
            'nguoi_nhan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
            'bai_dang_lien_quan' => 'nullable|integer|exists:BaiDang,id_bai_dang',
            'noi_dung' => 'required|string',
        ]);

        $tinNhan = TinNhan::create([
            'nguoi_gui' => $request->nguoi_gui,
            'nguoi_nhan' => $request->nguoi_nhan,
            'bai_dang_lien_quan' => $request->bai_dang_lien_quan,
            'noi_dung' => $request->noi_dung,
            'thoi_gian_gui' => now(), // Thêm thủ công thay vì rely vào DB
        ]);

        return response()->json($tinNhan, 201);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Dữ liệu không hợp lệ.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Đã có lỗi xảy ra.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Hiển thị thông tin chi tiết một tin nhắn.
     * GET /api/tin-nhan/{id}
     */
    public function show($id)
    {
        $tinNhan = TinNhan::with(['nguoiGui', 'nguoiNhan', 'baiDangLienQuan'])->find($id);

        if (!$tinNhan) {
            return response()->json(['message' => 'Không tìm thấy tin nhắn.'], 404);
        }

        return response()->json($tinNhan);
    }

    /**
     * Cập nhật thông tin một tin nhắn.
     * PUT/PATCH /api/tin-nhan/{id}
     */
    public function update(Request $request, $id)
    {
        $tinNhan = TinNhan::find($id);

        if (!$tinNhan) {
            return response()->json(['message' => 'Không tìm thấy tin nhắn.'], 404);
        }

        try {
            $request->validate([
                'nguoi_gui' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'nguoi_nhan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'bai_dang_lien_quan' => 'nullable|integer|exists:BaiDang,id_bai_dang',
                'noi_dung' => 'required|string',
            ]);

            $tinNhan->update($request->all());

            return response()->json($tinNhan);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa một tin nhắn.
     * DELETE /api/tin-nhan/{id}
     */
    public function destroy($id)
    {
        $tinNhan = TinNhan::find($id);

        if (!$tinNhan) {
            return response()->json(['message' => 'Không tìm thấy tin nhắn.'], 404);
        }

        $tinNhan->delete();

        return response()->json(['message' => 'Tin nhắn đã được xóa thành công.'], 204);
    }

    /**
 * Lấy danh sách tin nhắn giữa 2 người dùng
 * GET /api/tin-nhan/giua/{user1}/{user2}
 */
public function getTinNhanGiuaHaiNguoi($user1, $user2)
{
    $tinNhans = TinNhan::where(function ($query) use ($user1, $user2) {
        $query->where('nguoi_gui', $user1)->where('nguoi_nhan', $user2);
    })->orWhere(function ($query) use ($user1, $user2) {
        $query->where('nguoi_gui', $user2)->where('nguoi_nhan', $user1);
    })->orderBy('thoi_gian_gui')->get();

    return response()->json($tinNhans);
}
/**
 * Lấy danh sách tài khoản đã trò chuyện với người dùng
 * GET /api/tin-nhan/danh-sach-doi-tuong/{userId}
 */
public function danhSachDoiTuongChat($userId)
{
    // Những người user này đã GỬI tin nhắn TỚI
    $nguoiNhanIds = TinNhan::where('nguoi_gui', $userId)
        ->pluck('nguoi_nhan')
        ->unique();

    $dsNguoiNhan = \App\Models\TaiKhoan::select('id_tai_khoan', 'ho_ten', 'anh_dai_dien')
        ->whereIn('id_tai_khoan', $nguoiNhanIds)
        ->get();

    // Những người đã GỬI tin nhắn ĐẾN user này
    $nguoiGuiIds = TinNhan::where('nguoi_nhan', $userId)
        ->pluck('nguoi_gui')
        ->unique();

    $dsNguoiGui = \App\Models\TaiKhoan::select('id_tai_khoan', 'ho_ten', 'anh_dai_dien')
        ->whereIn('id_tai_khoan', $nguoiGuiIds)
        ->get();

    return response()->json([
        'da_gui_toi' => $dsNguoiNhan,
        'duoc_gui_tu' => $dsNguoiGui,
    ]);
}


}