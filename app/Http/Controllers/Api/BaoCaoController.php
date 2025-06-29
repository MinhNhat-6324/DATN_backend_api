<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaoCao;
use App\Models\BaiDang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BaoCaoController extends Controller
{
    // GET /api/bao-cao
    public function index()
    {
        $baoCaos = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->get();
        return response()->json($baoCaos);
    }

    // POST /api/bao-cao
    public function store(Request $request)
    {
        try {
            $request->validate([
                'ma_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                'trang_thai' => 'string|in:dang_cho,da_xu_ly,tu_choi',
            ]);

            $baoCao = BaoCao::create([
                'ma_bai_dang' => $request->ma_bai_dang,
                'id_tai_khoan_bao_cao' => $request->id_tai_khoan_bao_cao,
                'ly_do' => $request->ly_do,
                'mo_ta_them' => $request->mo_ta_them,
                'trang_thai' => $request->trang_thai ?? 'dang_cho',
                'thoi_gian_bao_cao' => now(),
            ]);

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

    // POST /api/bao-cao/bai-dang/{id_bai_dang}
    public function postByBaiDang(Request $request, $id_bai_dang)
    {
        try {
            $request->validate([
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                'trang_thai' => 'string|in:dang_cho,da_xu_ly,tu_choi',
            ]);

            $baiDang = BaiDang::find($id_bai_dang);
            if (!$baiDang) {
                return response()->json(['message' => 'Bài đăng không tồn tại.'], 404);
            }

            $baoCao = BaoCao::create([
                'ma_bai_dang' => $id_bai_dang,
                'id_tai_khoan_bao_cao' => $request->id_tai_khoan_bao_cao,
                'ly_do' => $request->ly_do,
                'mo_ta_them' => $request->mo_ta_them,
                'trang_thai' => $request->trang_thai ?? 'dang_cho',
                'thoi_gian_bao_cao' => now(),
            ]);

            return response()->json($baoCao, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tạo báo cáo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/bao-cao/{id}
    public function show($id)
    {
        $baoCao = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        return response()->json($baoCao);
    }

    // PUT /api/bao-cao/{id}
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

    // DELETE /api/bao-cao/{id}
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
