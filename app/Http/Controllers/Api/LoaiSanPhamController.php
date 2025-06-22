<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoaiSanPham; // Import Model LoaiSanPham
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoaiSanPhamController extends Controller
{
    /**
     * Lấy danh sách tất cả loại sản phẩm.
     * GET /api/loai-san-pham
     */
    public function index()
    {
        $loaiSanPhams = LoaiSanPham::all();
        return response()->json($loaiSanPhams);
    }

    /**
     * Tạo một loại sản phẩm mới.
     * POST /api/loai-san-pham
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'ten_loai' => 'required|string|max:100|unique:LoaiSanPham,ten_loai',
            ]);

            $loaiSanPham = LoaiSanPham::create($request->all());

            return response()->json($loaiSanPham, 201);
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
     * Hiển thị thông tin chi tiết một loại sản phẩm.
     * GET /api/loai-san-pham/{id}
     */
    public function show($id)
    {
        $loaiSanPham = LoaiSanPham::find($id);

        if (!$loaiSanPham) {
            return response()->json(['message' => 'Không tìm thấy loại sản phẩm.'], 404);
        }

        return response()->json($loaiSanPham);
    }

    /**
     * Cập nhật thông tin một loại sản phẩm.
     * PUT/PATCH /api/loai-san-pham/{id}
     */
    public function update(Request $request, $id)
    {
        $loaiSanPham = LoaiSanPham::find($id);

        if (!$loaiSanPham) {
            return response()->json(['message' => 'Không tìm thấy loại sản phẩm.'], 404);
        }

        try {
            $request->validate([
                'ten_loai' => 'required|string|max:100|unique:LoaiSanPham,ten_loai,' . $id . ',id_loai',
            ]);

            $loaiSanPham->update($request->all());

            return response()->json($loaiSanPham);
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
     * Xóa một loại sản phẩm.
     * DELETE /api/loai-san-pham/{id}
     */
    public function destroy($id)
    {
        $loaiSanPham = LoaiSanPham::find($id);

        if (!$loaiSanPham) {
            return response()->json(['message' => 'Không tìm thấy loại sản phẩm.'], 404);
        }

        // Kiểm tra xem có bài đăng nào đang sử dụng loại sản phẩm này không
        if ($loaiSanPham->baiDangs()->exists()) {
            return response()->json(['message' => 'Không thể xóa loại sản phẩm này vì có bài đăng đang sử dụng.'], 409); // 409 Conflict
        }

        $loaiSanPham->delete();

        return response()->json(['message' => 'Loại sản phẩm đã được xóa thành công.'], 204);
    }
}