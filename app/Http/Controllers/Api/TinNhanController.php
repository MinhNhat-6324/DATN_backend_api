<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TinNhan; // Import Model TinNhan
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\TinNhanEmail;
use App\Models\TaiKhoan;
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

// Route: GET /api/tin-nhan/giua/{user1}/{user2}
public function getTinNhanGiuaHaiNguoi(Request $request, $user1, $user2)
{
    if (!$request->has('bai_dang')) {
        return response()->json(['error' => 'Thiếu tham số bai_dang'], 400);
    }

    $idBaiDang = $request->input('bai_dang');

    $tinNhans = TinNhan::where(function ($q) use ($user1, $user2, $idBaiDang) {
        $q->where(function ($q2) use ($user1, $user2, $idBaiDang) {
            $q2->where('nguoi_gui', $user1)
                ->where('nguoi_nhan', $user2)
                ->where('bai_dang_lien_quan', $idBaiDang);
        })->orWhere(function ($q2) use ($user1, $user2, $idBaiDang) {
            $q2->where('nguoi_gui', $user2)
                ->where('nguoi_nhan', $user1)
                ->where('bai_dang_lien_quan', $idBaiDang);
        });
    })->orderBy('thoi_gian_gui')->get();

    return response()->json($tinNhans);
}


/**
 * Lấy danh sách tài khoản đã trò chuyện với người dùng
 * GET /api/tin-nhan/danh-sach-doi-tuong/{userId}
 */
public function danhSachDoiTuongChat($userId)
{
    // Lấy tất cả tin nhắn có liên quan tới người dùng
    $tinNhans = \App\Models\TinNhan::where('nguoi_gui', $userId)
        ->orWhere('nguoi_nhan', $userId)
        ->orderByDesc('thoi_gian_gui')
        ->get();

    $doiTuongs = [];

    foreach ($tinNhans as $tn) {
        $otherId = $tn->nguoi_gui == $userId ? $tn->nguoi_nhan : $tn->nguoi_gui;

        if (!isset($doiTuongs[$otherId])) {
            $taiKhoan = \App\Models\TaiKhoan::select('id_tai_khoan', 'ho_ten', 'anh_dai_dien')
                ->where('id_tai_khoan', $otherId)
                ->first();

            if ($taiKhoan) {
                $doiTuongs[$otherId] = [
                    'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                    'ho_ten' => $taiKhoan->ho_ten,
                    'anh_dai_dien' => $taiKhoan->anh_dai_dien,
                    'tin_nhan_cuoi' => $tn->noi_dung,
                    'thoi_gian' => $tn->thoi_gian_gui,
                ];
            }
        }
    }

    return response()->json(array_values($doiTuongs));
}

public function getDanhSachTinNhanTheoNguoiDung($idNguoiDung)
{
    $tinNhans = TinNhan::where('nguoi_gui', $idNguoiDung)
        ->orWhere('nguoi_nhan', $idNguoiDung)
        ->orderBy('thoi_gian_gui', 'asc')
        ->get();

    return response()->json($tinNhans);
}

public function sendEmailAndSave(Request $request)
{
    $request->validate([
        'nguoi_gui' => 'required|integer',
        'nguoi_nhan' => 'required|integer',
        'bai_dang_lien_quan' => 'nullable|integer',
        'noi_dung' => 'required|string',
    ]);

    $nguoiGui = TaiKhoan::findOrFail($request->nguoi_gui);
    $nguoiNhan = TaiKhoan::findOrFail($request->nguoi_nhan);

    // Gửi email: ĐÚNG THỨ TỰ (tên người gửi, nội dung)
    Mail::to($nguoiNhan->email)->send(new TinNhanEmail(
    $nguoiGui->ho_ten,
    $nguoiGui->email,
    $request->noi_dung
    ));


    // Lưu vào bảng tin_nhan
    $tinNhan = TinNhan::create([
        'nguoi_gui' => $request->nguoi_gui,
        'nguoi_nhan' => $request->nguoi_nhan,
        'bai_dang_lien_quan' => $request->bai_dang_lien_quan,
        'noi_dung' => $request->noi_dung,
        'thoi_gian_gui' => now(),
    ]);

    return response()->json([
        'message' => 'Gửi email thành công và đã lưu vào tin_nhan',
        'tin_nhan' => $tinNhan,
    ]);
}


}