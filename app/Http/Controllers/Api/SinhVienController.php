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
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_sinh_vien' => 'required|integer|exists:TaiKhoan,id_tai_khoan|unique:SinhVien,id_sinh_vien',
                'anh_the_sinh_vien' => 'nullable|string|max:255',
                'lop' => 'nullable|string|max:50',
                'chuyen_nganh' => 'nullable|string|max:100',
            ]);

            $sinhVien = SinhVien::create($request->all());

            return response()->json($sinhVien, 201);
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
     * Hiển thị thông tin chi tiết một sinh viên.
     * GET /api/sinh-vien/{id}
     */
    public function show($id)
    {
        $sinhVien = SinhVien::find($id);

        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
        }

        return response()->json($sinhVien);
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