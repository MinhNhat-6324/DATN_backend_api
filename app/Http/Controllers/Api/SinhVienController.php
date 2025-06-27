<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class SinhVienController extends Controller
{
    public function index()
    {
        $sinhViens = SinhVien::all();
        return response()->json($sinhViens);
    }

    // ✏️ Tạo mới sinh viên (commented out)
    // public function store(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'id_sinh_vien' => 'required|integer|exists:TaiKhoan,id_tai_khoan|unique:SinhVien,id_sinh_vien',
    //             'anh_the_sinh_vien' => 'nullable|string|max:255',
    //             'lop' => 'nullable|string|max:50',
    //             'chuyen_nganh_id' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
    //         ]);

    //         $sinhVien = SinhVien::create([
    //             'id_sinh_vien' => $request->id_sinh_vien,
    //             'lop' => $request->lop,
    //             'id_nganh' => $request->chuyen_nganh_id,
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

    public function show($id)
    {
        try {
            $sinhVien = SinhVien::with(['taiKhoan', 'chuyenNganh'])->find($id);

            if (!$sinhVien) {
                return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
            }

            $sinhVien->anh_the_sinh_vien = $this->getFullImageUrl($sinhVien->anh_the_sinh_vien);

            if ($sinhVien->taiKhoan) {
                $sinhVien->taiKhoan->anh_dai_dien = $this->getFullImageUrl($sinhVien->taiKhoan->anh_dai_dien);
            }

            $sinhVien->ten_chuyen_nganh = $sinhVien->chuyenNganh->ten_nganh ?? null;

            return response()->json($sinhVien);

        } catch (\Exception $e) {
            Log::error('Error fetching student details: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy thông tin sinh viên.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

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

    public function destroy($id)
    {
        $sinhVien = SinhVien::find($id);

        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy sinh viên.'], 404);
        }

        $sinhVien->delete();

        return response()->json(['message' => 'Sinh viên đã được xóa thành công.'], 204);
    }

    private function getFullImageUrl($path)
    {
        return $path ? url('storage/' . ltrim($path, '/')) : null;
    }
}