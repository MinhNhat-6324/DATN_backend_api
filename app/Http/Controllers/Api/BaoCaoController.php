<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaoCao; // Import Model BaoCao
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BaoCaoController extends Controller
{
    /**
     * Lấy danh sách tất cả báo cáo.
     * GET /api/bao-cao
     */
    public function index()
    {
        $baoCaos = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->get();
        return response()->json($baoCaos);
    }

    /**
     * Tạo một báo cáo mới.
     * POST /api/bao-cao
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'ma_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                'trang_thai' => 'string|in:dang_cho,da_xu_ly,tu_choi',
                // 'thoi_gian_bao_cao' không cần validate
            ]);

            $baoCao = BaoCao::create($request->all());

            return response()->json($baoCao, 201);
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
     * Hiển thị thông tin chi tiết một báo cáo.
     * GET /api/bao-cao/{id}
     */
    public function show($id)
    {
        $baoCao = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        return response()->json($baoCao);
    }

    /**
     * Cập nhật thông tin một báo cáo.
     * PUT/PATCH /api/bao-cao/{id}
     */
    public function update(Request $request, $id)
    {
        $baoCao = BaoCao::find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        try {
            $request->validate([
                'ma_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                'trang_thai' => 'string|in:dang_cho,da_xu_ly,tu_choi',
            ]);

            $baoCao->update($request->all());

            return response()->json($baoCao);
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
     * Xóa một báo cáo.
     * DELETE /api/bao-cao/{id}
     */
    public function destroy($id)
    {
        $baoCao = BaoCao::find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        $baoCao->delete();

        return response()->json(['message' => 'Báo cáo đã được xóa thành công.'], 204);
    }
}