<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnhBaiDang; // Import Model AnhBaiDang
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnhBaiDangController extends Controller
{
    /**
     * Lấy danh sách tất cả ảnh bài đăng.
     * GET /api/anh-bai-dang
     */
    public function index()
    {
        $anhBaiDangs = AnhBaiDang::all();
        return response()->json($anhBaiDangs);
    }

    /**
     * Tạo một ảnh bài đăng mới.
     * POST /api/anh-bai-dang
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'duong_dan' => 'required|string|max:255', // Nếu là upload file, sẽ phức tạp hơn
                'thu_tu' => 'nullable|integer',
            ]);

            $anhBaiDang = AnhBaiDang::create($request->all());

            return response()->json($anhBaiDang, 201);
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
     * Hiển thị thông tin chi tiết một ảnh bài đăng.
     * GET /api/anh-bai-dang/{id}
     */
    public function show($id)
    {
        $anhBaiDang = AnhBaiDang::find($id);

        if (!$anhBaiDang) {
            return response()->json(['message' => 'Không tìm thấy ảnh bài đăng.'], 404);
        }

        return response()->json($anhBaiDang);
    }

    /**
     * Cập nhật thông tin một ảnh bài đăng.
     * PUT/PATCH /api/anh-bai-dang/{id}
     */
    public function update(Request $request, $id)
    {
        $anhBaiDang = AnhBaiDang::find($id);

        if (!$anhBaiDang) {
            return response()->json(['message' => 'Không tìm thấy ảnh bài đăng.'], 404);
        }

        try {
            $request->validate([
                'id_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'duong_dan' => 'required|string|max:255',
                'thu_tu' => 'nullable|integer',
            ]);

            $anhBaiDang->update($request->all());

            return response()->json($anhBaiDang);
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
     * Xóa một ảnh bài đăng.
     * DELETE /api/anh-bai-dang/{id}
     */
    public function destroy($id)
    {
        $anhBaiDang = AnhBaiDang::find($id);

        if (!$anhBaiDang) {
            return response()->json(['message' => 'Không tìm thấy ảnh bài đăng.'], 404);
        }

        $anhBaiDang->delete();

        return response()->json(['message' => 'Ảnh bài đăng đã được xóa thành công.'], 204);
    }
}