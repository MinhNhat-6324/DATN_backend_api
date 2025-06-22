<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaiDang; // Import Model BaiDang
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BaiDangController extends Controller
{
    /**
     * Lấy danh sách tất cả bài đăng.
     * GET /api/bai-dang
     */
    public function index()
    {
        // Có thể thêm with(['taiKhoan', 'loaiSanPham', 'chuyenNganhSanPham']) để load mối quan hệ
        $baiDangs = BaiDang::with(['taiKhoan', 'loaiSanPham', 'chuyenNganhSanPham'])->get();
        return response()->json($baiDangs);
    }

    /**
     * Tạo một bài đăng mới.
     * POST /api/bai-dang
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_tai_khoan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'tieu_de' => 'required|string|max:255',
                'do_moi' => 'nullable|integer|between:0,100',
                'id_loai' => 'required|integer|exists:LoaiSanPham,id_loai',
                'id_nganh' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
                'gia' => 'required|numeric|min:0',
                'trang_thai' => 'string|in:san_sang,dang_giao_dich,hoan_thanh',
                // 'ngay_dang' không cần validate vì có DEFAULT CURRENT_TIMESTAMP
            ]);

            $baiDang = BaiDang::create($request->all());

            return response()->json($baiDang, 201);
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
     * Hiển thị thông tin chi tiết một bài đăng.
     * GET /api/bai-dang/{id}
     */
    public function show($id)
    {
        // Có thể thêm with để load mối quan hệ khi hiển thị chi tiết
        $baiDang = BaiDang::with(['taiKhoan', 'loaiSanPham', 'chuyenNganhSanPham', 'anhBaiDangs', 'tinNhans', 'baoCaos'])->find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
        }

        return response()->json($baiDang);
    }

    /**
     * Cập nhật thông tin một bài đăng.
     * PUT/PATCH /api/bai-dang/{id}
     */
    public function update(Request $request, $id)
    {
        $baiDang = BaiDang::find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
        }

        try {
            $request->validate([
                'id_tai_khoan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'tieu_de' => 'required|string|max:255',
                'do_moi' => 'nullable|integer|between:0,100',
                'id_loai' => 'required|integer|exists:LoaiSanPham,id_loai',
                'id_nganh' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
                'gia' => 'required|numeric|min:0',
                'trang_thai' => 'string|in:san_sang,dang_giao_dich,hoan_thanh',
            ]);

            $baiDang->update($request->all());

            return response()->json($baiDang);
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
     * Xóa một bài đăng.
     * DELETE /api/bai-dang/{id}
     */
    public function destroy($id)
    {
        $baiDang = BaiDang::find($id);

        if (!$baiDang) {
            return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
        }

        $baiDang->delete(); // Các AnhBaiDang, TinNhan, BaoCao liên quan sẽ bị xử lý bởi ON DELETE CASCADE/SET NULL trong DB

        return response()->json(['message' => 'Bài đăng đã được xóa thành công.'], 204);
    }
}