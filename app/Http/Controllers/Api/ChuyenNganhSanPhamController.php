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
}