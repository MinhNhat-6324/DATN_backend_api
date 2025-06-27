<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SinhVien; // Import Model SinhVien
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SinhVienController extends Controller
{
    /**
     * Lấy danh sách tất cả sinh viên.
     * GET /api/sinh-vien
     */
    public function index()
    {
        $sinhViens = SinhVien::all();
        return response()->json($sinhViens);
    }

    /**
     * Tạo một sinh viên mới.
     * POST /api/sinh-vien
     * Lưu ý: id_sinh_vien phải tồn tại trong TaiKhoan
      */
// public function store(Request $request)
// {
//     try {
//         $request->validate([
//             'id_sinh_vien' => 'required|integer|exists:TaiKhoan,id_tai_khoan|unique:SinhVien,id_sinh_vien',
//             'anh_the_sinh_vien' => 'nullable|string|max:255',
//             'lop' => 'nullable|string|max:50',
//             'chuyen_nganh_id' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh', // 👈 dùng đúng tên
//         ]);

//         $sinhVien = SinhVien::create([
//             'id_sinh_vien' => $request->id_sinh_vien,
//             'lop' => $request->lop,
//             'id_nganh' => $request->chuyen_nganh_id, // 👈 map đúng sang cột DB
//             'anh_the_sinh_vien' => $request->anh_the_sinh_vien,
//         ]);

//         return response()->json($sinhVien, 201);
//     } catch (ValidationException $e) {
//         return response()->json([
//             'message' => 'Dữ liệu không hợp lệ.',
//             'errors' => $e->errors()
//         ], 422);
//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => 'Đã có lỗi xảy ra.',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

    /**
     * Hiển thị thông tin chi tiết một sinh viên.
     * GET /api/sinh-vien/{id}
     */
    public function show($id)
    {
        try {
            // Eager load mối quan hệ 'taiKhoan' và 'chuyenNganh'
            $sinhVien = SinhVien::with(['taiKhoan', 'chuyenNganh'])->find($id);

            if (!$sinhVien) {
                return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
            }

            // Chuyển đổi đường dẫn ảnh thẻ sinh viên thành URL đầy đủ
            $sinhVien->anh_the_sinh_vien = $this->getFullImageUrl($sinhVien->anh_the_sinh_vien);

            // Chuyển đổi đường dẫn ảnh đại diện của tài khoản nếu có
            if ($sinhVien->taiKhoan) {
                $sinhVien->taiKhoan->anh_dai_dien = $this->getFullImageUrl($sinhVien->taiKhoan->anh_dai_dien);
            }

            // Thêm tên chuyên ngành trực tiếp vào đối tượng sinh viên để dễ truy cập
            if ($sinhVien->chuyenNganh) {
                $sinhVien->ten_chuyen_nganh = $sinhVien->chuyenNganh->ten_nganh;
            } else {
                $sinhVien->ten_chuyen_nganh = null;
            }
            // Tùy chọn: bỏ đối tượng chuyenNganh gốc nếu không cần thiết
            // unset($sinhVien->chuyenNganh);


            return response()->json($sinhVien);

        } catch (\Exception $e) {
            Log::error('Error fetching student details: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy thông tin sinh viên.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một sinh viên.
     * PUT/PATCH /api/sinh-vien/{id}
     */
    public function update(Request $request, $id)
    {
        $sinhVien = SinhVien::find($id);

        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
        }

        try {
            $request->validate([
                'anh_the_sinh_vien' => 'nullable|string|max:255',
                'lop' => 'nullable|string|max:50',
                'chuyen_nganh' => 'nullable|string|max:100',
            ]);

            $sinhVien->update($request->all());

            return response()->json($sinhVien);
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
     * Xóa một sinh viên.
     * DELETE /api/sinh-vien/{id}
     */
    public function destroy($id)
    {
        $sinhVien = SinhVien::find($id);

        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
        }

        $sinhVien->delete();

        return response()->json(['message' => 'Sinh viên đã được xóa thành công.'], 204);
    }
}