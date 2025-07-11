<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChuyenNganhSanPham;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChuyenNganhSanPhamController extends Controller
{
    /**
     * Lấy danh sách tất cả chuyên ngành sản phẩm.
     * GET /api/chuyen-nganh-san-pham
     */
    public function index()
    {
        try {
            $chuyenNganhSanPhams = ChuyenNganhSanPham::all();
            return response()->json([
                'status' => 'success', // Thêm trường status
                'data' => $chuyenNganhSanPhams // Dữ liệu nằm trong trường 'data'
            ]);
        } catch (\Exception $e) {
            // Xử lý lỗi nếu có vấn đề khi truy vấn database
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách chuyên ngành sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function thongKeBaiDangTheoChuyenNganh()
{
    try {
        // Lấy tất cả chuyên ngành và đếm số bài đăng liên quan (sử dụng withCount)
        $thongKe = ChuyenNganhSanPham::withCount('baiDang')->get(['id_nganh', 'ten_nganh']);

        return response()->json([
            'status' => 'success',
            'data' => $thongKe
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Không thể thống kê bài đăng theo chuyên ngành.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}