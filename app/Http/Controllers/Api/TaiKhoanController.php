<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoan; // Import Model TaiKhoan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Để mã hóa mật khẩu
use Illuminate\Validation\ValidationException; // Để bắt lỗi validation
use Illuminate\Support\Facades\Log; // Import Log facade để ghi log lỗi

class TaiKhoanController extends Controller
{
    /**
     * Lấy danh sách tất cả tài khoản.
     * Hỗ trợ phân trang và tìm kiếm theo email, họ tên hoặc số điện thoại.
     * GET /api/tai-khoan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Lấy tham số phân trang từ request, mặc định là 10 mục mỗi trang
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search'); // Tham số tìm kiếm

            $query = TaiKhoan::query();

            // Áp dụng tìm kiếm nếu có tham số 'search'
            if ($search) {
                $query->where('email', 'like', '%' . $search . '%')
                      ->orWhere('ho_ten', 'like', '%' . $search . '%')
                      ->orWhere('so_dien_thoai', 'like', '%' . $search . '%');
            }

            // Phân trang kết quả
            $accounts = $query->paginate($perPage);

            // Trả về phản hồi JSON với danh sách tài khoản đã phân trang
            return response()->json([
                'message' => 'Lấy danh sách tài khoản thành công.',
                'data' => $accounts->items(), // Chỉ lấy các mục hiện tại trên trang
                'pagination' => [ // Thông tin phân trang
                    'total' => $accounts->total(),
                    'per_page' => $accounts->perPage(),
                    'current_page' => $accounts->currentPage(),
                    'last_page' => $accounts->lastPage(),
                    'from' => $accounts->firstItem(),
                    'to' => $accounts->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            // Ghi log lỗi để dễ dàng debug
            Log::error('Error fetching accounts: ' . $e->getMessage(), ['exception' => $e]);

            // Trả về phản hồi lỗi server
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy danh sách tài khoản.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo một tài khoản mới.
     * POST /api/tai-khoan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Kiểm tra dữ liệu đầu vào (validation)
            $request->validate([
                'email' => 'required|email|unique:TaiKhoan,email|regex:/@caothang\.edu\.vn$/',
                'ho_ten' => 'required|string|max:100',
                'mat_khau' => 'required|string|min:6',
                'gioi_tinh' => 'boolean',
                'anh_dai_dien' => 'nullable|string|max:255',
                'so_dien_thoai' => 'nullable|string|max:20',
                'trang_thai' => 'boolean',
                'loai_tai_khoan' => 'boolean',
            ]);

            // Tạo tài khoản mới
            $taiKhoan = TaiKhoan::create([
                'email' => $request->email,
                'ho_ten' => $request->ho_ten,
                'mat_khau' => Hash::make($request->mat_khau), // Mã hóa mật khẩu trước khi lưu
                'gioi_tinh' => $request->input('gioi_tinh', 1), // Sử dụng input() để cung cấp giá trị mặc định nếu không có
                'anh_dai_dien' => $request->anh_dai_dien,
                'so_dien_thoai' => $request->so_dien_thoai,
                'trang_thai' => $request->input('trang_thai', 1),
                'loai_tai_khoan' => $request->input('loai_tai_khoan', 0),
            ]);

            // Trả về tài khoản vừa tạo với mã trạng thái 201 (Created)
            return response()->json($taiKhoan, 201);
        } catch (ValidationException $e) {
            // Bắt lỗi validation và trả về thông báo lỗi chi tiết
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Bắt các lỗi khác và trả về lỗi server
            return response()->json([
                'message' => 'Đã có lỗi xảy ra.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Hiển thị thông tin chi tiết một tài khoản.
     * GET /api/tai-khoan/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $taiKhoan = TaiKhoan::find($id); // Tìm tài khoản theo ID

        if (!$taiKhoan) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404); // 404 Not Found
        }

        return response()->json($taiKhoan);
    }

    /**
     * Cập nhật thông tin một tài khoản.
     * PUT/PATCH /api/tai-khoan/{id}
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $taiKhoan = TaiKhoan::find($id);

        if (!$taiKhoan) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }

        try {
            // Kiểm tra dữ liệu đầu vào khi cập nhật
            $request->validate([
                // email cần unique nhưng bỏ qua chính nó
                'email' => 'required|email|unique:TaiKhoan,email,' . $id . ',id_tai_khoan|regex:/@caothang\.edu\.vn$/',
                'ho_ten' => 'required|string|max:100',
                'mat_khau' => 'nullable|string|min:6', // Mật khẩu có thể không đổi, nên nullable
                'gioi_tinh' => 'boolean',
                'anh_dai_dien' => 'nullable|string|max:255',
                'so_dien_thoai' => 'nullable|string|max:20',
                'trang_thai' => 'boolean',
                'loai_tai_khoan' => 'boolean',
            ]);

            // Cập nhật các trường
            $taiKhoan->email = $request->email;
            $taiKhoan->ho_ten = $request->ho_ten;
            if ($request->has('mat_khau')) { // Chỉ cập nhật mật khẩu nếu có gửi lên
                $taiKhoan->mat_khau = Hash::make($request->mat_khau);
            }
            $taiKhoan->gioi_tinh = $request->input('gioi_tinh', $taiKhoan->gioi_Giaithoai); // Giữ giá trị cũ nếu không gửi lên
            $taiKhoan->anh_dai_dien = $request->anh_dai_dien;
            $taiKhoan->so_dien_thoai = $request->so_dien_thoai;
            $taiKhoan->trang_thai = $request->input('trang_thai', $taiKhoan->trang_thai);
            $taiKhoan->loai_tai_khoan = $request->input('loai_tai_khoan', $taiKhoan->loai_tai_khoan);
            $taiKhoan->save(); // Lưu thay đổi vào database

            return response()->json($taiKhoan);
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
     * Xóa một tài khoản.
     * DELETE /api/tai-khoan/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $taiKhoan = TaiKhoan::find($id);

        if (!$taiKhoan) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }

        $taiKhoan->delete(); // Xóa tài khoản

        // Trả về mã trạng thái 204 (No Content) báo hiệu xóa thành công nhưng không có dữ liệu trả về
        return response()->json(['message' => 'Tài khoản đã được xóa thành công.'], 204);
    }
}
