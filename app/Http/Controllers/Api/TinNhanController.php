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
                // 'thoi_gian_gui' không cần validate vì có DEFAULT CURRENT_TIMESTAMP
            ]);

            $tinNhan = TinNhan::create($request->all());

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
}