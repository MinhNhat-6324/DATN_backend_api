<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaiDang;
use Illuminate\Http\Request;

class BaiDangController extends Controller
{
    /**
     * GET /api/bai-dang
     * Lấy danh sách bài đăng + ảnh sản phẩm
     */
    public function index()
    {
        $baiDangs = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'gia',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
            ])
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    } // ✅ ĐÓNG dấu ngoặc của index() ở đây

    /**
     * GET /api/bai-dang/nganh/{id}
     * Lấy danh sách bài đăng theo ngành
     */
    public function getByNganh($id_nganh)
    {
        $baiDang = BaiDang::with([
            'anhBaiDang' => function ($query) {
                $query->select('id_bai_dang', 'duong_dan', 'thu_tu')->orderBy('thu_tu');
            },
            'chuyenNganhSanPham:id_nganh,ten_nganh'
        ])
        ->select([
            'id_bai_dang',
            'id_tai_khoan',
            'tieu_de',
            'gia',
            'do_moi',
            'trang_thai',
            'ngay_dang',
            'id_loai',
            'id_nganh',
        ])
        ->where('id_nganh', $id_nganh)
        ->orderByDesc('ngay_dang')
        ->get();

        return response()->json($baiDang);
    }

    /**
     * GET /api/bai-dang/{id}
     * Chi tiết một bài đăng
     */
    public function show($id)
    {
        $baiDang = BaiDang::with(['anhBaiDang' => function ($query) {
                $query->select('id_bai_dang', 'duong_dan', 'thu_tu')->orderBy('thu_tu');
            }])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'gia',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
            ])
            ->find($id);

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