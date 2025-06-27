<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoan; // Import Model TaiKhoan
use App\Models\SinhVien; // Import Model SinhVien (để lưu thông tin sinh viên)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Để mã hóa mật khẩu
use Illuminate\Validation\ValidationException; // Để bắt lỗi validation
use Illuminate\Support\Facades\Log; // Import Log facade để ghi log lỗi
use Illuminate\Support\Facades\Storage; // Import Storage facade để xử lý file
use Illuminate\Support\Str; // Import Str facade để kiểm tra và tạo chuỗi ngẫu nhiên
use Illuminate\Support\Facades\DB; // Import DB facade for transactions

class TaiKhoanController extends Controller
{
    /**
     * Helper function to convert relative storage paths to full public URLs.
     * This ensures Flutter's NetworkImage can load them.
     * @param string|null $path The relative path stored in the database (e.g., 'avatars/image.jpg')
     * @return string|null The full public URL (e.g., 'http://your-app.com/storage/avatars/image.jpg')
     */
   private function getFullImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Bước 1: Kiểm tra xem nó có phải là một URL đầy đủ (tuyệt đối) không
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path; // Nếu đã là URL đầy đủ, trả về ngay lập tức
        }

        // Bước 2: Nếu không phải URL đầy đủ, thì nó là một đường dẫn tương đối.
        // Laravel's asset() helper có thể xử lý các đường dẫn bắt đầu bằng '/storage/'
        // một cách chính xác nếu symlink đã được tạo.
        // Ví dụ: /storage/student_cards/image.png
        if (Str::startsWith($path, '/storage/')) {
            return asset($path);
        }

        // Bước 3: Nếu nó là một đường dẫn tương đối nhưng không bắt đầu bằng '/storage/',
        // giả định nó là đường dẫn tương đối so với thư mục storage/app/public.
        // Ví dụ: student_cards/image.png
        // Loại bỏ dấu gạch chéo đầu tiên để tránh asset('storage//...')
        $cleanPath = ltrim($path, '/'); 
        return asset('storage/' . $cleanPath);
    }
    /**
     * Lấy danh sách tất cả tài khoản.
     * Hỗ trợ phân trang và tìm kiếm theo email, họ tên hoặc số điện thoại.
     * Có thể lọc theo trạng thái (trang_thai) và loại tài khoản (loai_tai_khoan).
     * GET /api/tai-khoan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            $query = TaiKhoan::query();

            // Lọc theo loai_tai_khoan (nếu có) - giả định bạn muốn lọc theo loai_tai_khoan = 0 cho sinh viên
            // Nếu bạn muốn hiển thị cả admin và sinh viên, hãy xóa dòng này hoặc thêm logic phức tạp hơn
            $query->where('loai_tai_khoan', 0); // Giữ lại để chỉ lấy tài khoản sinh viên

            // *******************************************************************
            // ĐÂY LÀ PHẦN SỬA LỖI: ÁP DỤNG LỌC TRẠNG THÁI TỪ REQUEST PARAMETER
            // *******************************************************************
            if ($request->has('trang_thai')) {
                $status = $request->input('trang_thai');
                $query->where('trang_thai', $status);
            } else {
                // Nếu không có tham số trang_thai, mặc định chỉ lấy trạng thái 1 (Đã kích hoạt)
                // HOẶC bạn có thể xóa dòng này để lấy tất cả trạng thái nếu không có tham số
                $query->where('trang_thai', 1); // Mặc định chỉ lấy tài khoản đã kích hoạt nếu không có tham số trạng_thai
            }


            // Áp dụng tìm kiếm nếu có tham số 'search'
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('email', 'like', '%' . $search . '%')
                      ->orWhere('ho_ten', 'like', '%' . $search . '%')
                      ->orWhere('so_dien_thoai', 'like', '%' . $search . '%');
                });
            }

            $accounts = $query->paginate($perPage);

            // Chuyển đổi đường dẫn ảnh đại diện thành URL đầy đủ cho mỗi tài khoản
            foreach ($accounts->items() as $account) {
                $account->anh_dai_dien = $this->getFullImageUrl($account->anh_dai_dien);
            }

            return response()->json([
                'message' => 'Lấy danh sách tài khoản thành công.',
                'data' => $accounts->items(),
                'pagination' => [
                    'total' => $accounts->total(),
                    'per_page' => $accounts->perPage(),
                    'current_page' => $accounts->currentPage(),
                    'last_page' => $accounts->lastPage(),
                    'from' => $accounts->firstItem(),
                    'to' => $accounts->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching accounts: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy danh sách tài khoản.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách tài khoản đang chờ duyệt.
     * Hỗ trợ phân trang và tìm kiếm.
     * Chỉ lấy những tài khoản có loai_tai_khoan = 0 VÀ trạng thái = 0 (Chờ duyệt).
     * GET /api/tai-khoan/pending
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function danh_sach_cho(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');

            // CHỈ LẤY CÁC TÀI KHOẢN CÓ loai_tai_khoan = 0 VÀ trạng thái = 0 (Chờ duyệt)
            $query = TaiKhoan::where('loai_tai_khoan', 0)->where('trang_thai', 0);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('email', 'like', '%' . $search . '%')
                      ->orWhere('ho_ten', 'like', '%' . $search . '%')
                      ->orWhere('so_dien_thoai', 'like', '%' . $search . '%');
                });
            }

            $accounts = $query->paginate($perPage);

            // Chuyển đổi đường dẫn ảnh đại diện thành URL đầy đủ cho mỗi tài khoản
            foreach ($accounts->items() as $account) {
                $account->anh_dai_dien = $this->getFullImageUrl($account->anh_dai_dien);
            }

            return response()->json([
                'message' => 'Lấy danh sách tài khoản chờ duyệt thành công.',
                'data' => $accounts->items(),
                'pagination' => [
                    'total' => $accounts->total(),
                    'per_page' => $accounts->perPage(),
                    'current_page' => $accounts->currentPage(),
                    'last_page' => $accounts->lastPage(),
                    'from' => $accounts->firstItem(),
                    'to' => $accounts->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching pending accounts: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy danh sách tài khoản chờ duyệt.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tạo tài khoản mới.
     * POST /api/tai-khoan
     * Trạng thái mặc định cho tài khoản người dùng mới là 0 (Chờ duyệt).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
     public function store(Request $request)
{
    try {
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'email' => 'required|email|unique:TaiKhoan,email|regex:/@caothang\.edu\.vn$/',
            'ho_ten' => 'required|string|max:100',
            'mat_khau' => 'required|string|min:6',
            'gioi_tinh' => 'required|boolean',
            'so_dien_thoai' => 'nullable|string|max:20',
            'anh_dai_dien' => 'nullable|image|max:2048',
            'loai_tai_khoan' => 'required|boolean',
            'trang_thai' => 'nullable|boolean',

            // Validation cho dữ liệu SinhVien nếu là người dùng
            'sinh_vien' => 'nullable|array',
            'sinh_vien.lop' => 'required_if:loai_tai_khoan,0|string|max:50',
            'sinh_vien.chuyen_nganh_id' => 'required_if:loai_tai_khoan,0|integer|exists:ChuyenNganhSanPham,id_nganh',
            'sinh_vien.anh_the_sinh_vien' => 'nullable|image|max:2048',
        ]);

        // Tạo tài khoản
        $taiKhoan = new TaiKhoan();
        $taiKhoan->email = $validatedData['email'];
        $taiKhoan->ho_ten = $validatedData['ho_ten'];
        $taiKhoan->mat_khau = Hash::make($validatedData['mat_khau']);
        $taiKhoan->gioi_tinh = $validatedData['gioi_tinh'];
        $taiKhoan->so_dien_thoai = $validatedData['so_dien_thoai'] ?? null;
        $taiKhoan->loai_tai_khoan = $validatedData['loai_tai_khoan'];
        $taiKhoan->trang_thai = $validatedData['trang_thai'] ?? 0;

        // Ảnh đại diện
        if ($request->hasFile('anh_dai_dien')) {
            $imageFile = $request->file('anh_dai_dien');
            $filename = time() . '_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $relativePath = $imageFile->storeAs('avatars', $filename, 'public');
            $taiKhoan->anh_dai_dien = $relativePath;
        }

        $taiKhoan->save();

        // Nếu là người dùng -> tạo bản ghi SinhVien
        if ($taiKhoan->loai_tai_khoan == 0 && $request->has('sinh_vien')) {
            $sinhVien = new SinhVien();
            $sinhVien->id_sinh_vien = $taiKhoan->id_tai_khoan;
            $sinhVien->lop = data_get($validatedData, 'sinh_vien.lop');
            $sinhVien->id_nganh = data_get($validatedData, 'sinh_vien.chuyen_nganh_id');

            if ($request->hasFile('sinh_vien.anh_the_sinh_vien')) {
                $studentCardFile = $request->file('sinh_vien.anh_the_sinh_vien');
                $cardFilename = time() . '_student_card_' . Str::random(10) . '.' . $studentCardFile->getClientOriginalExtension();
                $relativePath = $studentCardFile->storeAs('student_cards', $cardFilename, 'public');
                $sinhVien->anh_the_sinh_vien = $relativePath;
            }

            $sinhVien->save();

            // Eager load
            $taiKhoan->load(['sinhVien' => function ($query) {
                $query->with('chuyenNganh');
            }]);

            if ($taiKhoan->sinhVien && $taiKhoan->sinhVien->chuyenNganh) {
                $taiKhoan->sinhVien->ten_chuyen_nganh = $taiKhoan->sinhVien->chuyenNganh->ten_nganh;
                unset($taiKhoan->sinhVien->chuyenNganh);
            } else {
                $taiKhoan->sinhVien->ten_chuyen_nganh = null;
            }
        }

        // Chuyển đổi đường dẫn ảnh thành URL đầy đủ
        $taiKhoan->anh_dai_dien = $this->getFullImageUrl($taiKhoan->anh_dai_dien);
        if ($taiKhoan->sinhVien) {
            $taiKhoan->sinhVien->anh_the_sinh_vien = $this->getFullImageUrl($taiKhoan->sinhVien->anh_the_sinh_vien);
        }

        return response()->json([
            'message' => 'Tài khoản đã được tạo thành công và đang chờ duyệt.',
            'data' => $taiKhoan,
        ], 201);

    } catch (ValidationException $e) {
        Log::warning('Validation error during account creation: ' . json_encode($e->errors()));
        return response()->json([
            'message' => 'Dữ liệu không hợp lệ.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error creating account: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'message' => 'Đã có lỗi xảy ra khi tạo tài khoản.',
            'error' => $e->getMessage()
        ], 500);
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
        try {
            // Eager load mối quan hệ 'sinhVien' và sâu hơn là 'chuyenNganhSanPham' bên trong 'sinhVien'
            $taiKhoan = TaiKhoan::with(['sinhVien.chuyenNganhSanPham'])->find($id);

            if (!$taiKhoan) {
                return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
            }

            // Chuyển đổi đường dẫn ảnh đại diện thành URL đầy đủ
            $taiKhoan->anh_dai_dien = $this->getFullImageUrl($taiKhoan->anh_dai_dien);

            // Xử lý thông tin sinh viên nếu có
            if ($taiKhoan->sinhVien) {
                // Chuyển đổi đường dẫn ảnh thẻ sinh viên thành URL đầy đủ
                $taiKhoan->sinhVien->anh_the_sinh_vien = $this->getFullImageUrl($taiKhoan->sinhVien->anh_the_sinh_vien);

                // Lấy tên chuyên ngành và gán trực tiếp vào thuộc tính của sinh viên
                // để dễ dàng truy cập từ Flutter
                if ($taiKhoan->sinhVien->chuyenNganhSanPham) {
                    $taiKhoan->sinhVien->chuyen_nganh = $taiKhoan->sinhVien->chuyenNganhSanPham->ten_nganh;
                } else {
                    $taiKhoan->sinhVien->chuyen_nganh = null; // Hoặc 'Không có chuyên ngành'
                }
                
                // Loại bỏ đối tượng chuyenNganhSanPham gốc nếu bạn chỉ muốn gửi tên ngành
                unset($taiKhoan->sinhVien->chuyenNganhSanPham);
            }

            return response()->json($taiKhoan, 200); // Trả về 200 OK

        } catch (\Exception $e) {
            Log::error('Error fetching account details: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi lấy thông tin tài khoản.',
                'error_detail' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin một tài khoản (Dành cho admin hoặc chỉnh sửa hồ sơ chung).
     * PUT/PATCH /api/tai-khoan/{id}
     * Lưu ý: Phương thức này không dành cho OCR mà cho cập nhật thông tin chung.
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
        $request->validate([
            'email' => [
                'sometimes',
                'email',
                'unique:TaiKhoan,email,' . $id . ',id_tai_khoan',
                'regex:/@caothang\.edu\.vn$/',
            ],
            'ho_ten' => 'sometimes|string|max:100',
            'mat_khau' => 'sometimes|nullable|string|min:6',
            'gioi_tinh' => 'sometimes|boolean',
            'anh_dai_dien' => 'sometimes|nullable|string|max:255',
            'anh_dai_dien_file' => 'sometimes|nullable|image|max:2048',
            'so_dien_thoai' => 'sometimes|nullable|string|max:20',
            'trang_thai' => 'sometimes|integer|in:0,1,2',
            'loai_tai_khoan' => 'sometimes|boolean',

            // Cập nhật SinhVien
            'sinh_vien' => 'sometimes|array',
            'sinh_vien.lop' => 'sometimes|string|max:50',
            'sinh_vien.chuyen_nganh_id' => 'sometimes|integer|exists:ChuyenNganhSanPham,id_nganh',
            'sinh_vien.anh_the_sinh_vien' => 'sometimes|nullable|string|max:255',
            'sinh_vien.anh_the_sinh_vien_file' => 'sometimes|nullable|image|max:2048',
        ]);

        if ($request->has('email')) {
            $taiKhoan->email = $request->email;
        }
        if ($request->has('ho_ten')) {
            $taiKhoan->ho_ten = $request->ho_ten;
        }
        if ($request->has('mat_khau') && !empty($request->mat_khau)) {
            $taiKhoan->mat_khau = Hash::make($request->mat_khau);
        }
        if ($request->has('gioi_tinh')) {
            $taiKhoan->gioi_tinh = $request->gioi_tinh;
        }

        if ($request->hasFile('anh_dai_dien_file')) {
            $imageFile = $request->file('anh_dai_dien_file');
            $filename = time() . '_avatar_' . Str::random(10) . '.' . $imageFile->getClientOriginalExtension();
            $relativePath = $imageFile->storeAs('avatars', $filename, 'public');
            $taiKhoan->anh_dai_dien = $relativePath;
        } else if ($request->has('anh_dai_dien')) {
            $taiKhoan->anh_dai_dien = $request->input('anh_dai_dien');
        }

        if ($request->has('so_dien_thoai')) {
            $taiKhoan->so_dien_thoai = $request->so_dien_thoai;
        }
        if ($request->has('trang_thai')) {
            $taiKhoan->trang_thai = $request->trang_thai;
        }
        if ($request->has('loai_tai_khoan')) {
            $taiKhoan->loai_tai_khoan = $request->loai_tai_khoan;
        }

        $taiKhoan->save();

        // Cập nhật sinh viên nếu là loại tài khoản 0
        if ($request->has('sinh_vien') && $taiKhoan->loai_tai_khoan == 0) {
            $sinhVien = $taiKhoan->sinhVien ?? new SinhVien([
                'id_sinh_vien' => $taiKhoan->id_tai_khoan,
                'id_tai_khoan' => $taiKhoan->id_tai_khoan,
            ]);

            if ($request->has('sinh_vien.lop')) {
                $sinhVien->lop = $request->input('sinh_vien.lop');
            }

            if ($request->has('sinh_vien.chuyen_nganh_id')) {
                $sinhVien->id_nganh = $request->input('sinh_vien.chuyen_nganh_id');
            }

            if ($request->hasFile('sinh_vien.anh_the_sinh_vien_file')) {
                $studentCardFile = $request->file('sinh_vien.anh_the_sinh_vien_file');
                $cardFilename = time() . '_student_card_update_' . Str::random(10) . '.' . $studentCardFile->getClientOriginalExtension();
                $relativeCardPath = $studentCardFile->storeAs('student_cards', $cardFilename, 'public');
                $sinhVien->anh_the_sinh_vien = $relativeCardPath;
            } else if ($request->has('sinh_vien.anh_the_sinh_vien')) {
                $sinhVien->anh_the_sinh_vien = $request->input('sinh_vien.anh_the_sinh_vien');
            }

            $sinhVien->save();
            $taiKhoan->setRelation('sinhVien', $sinhVien);
        }

        // Chuyển đổi đường dẫn ảnh thành URL đầy đủ
        $taiKhoan->anh_dai_dien = $this->getFullImageUrl($taiKhoan->anh_dai_dien);
        if ($taiKhoan->sinhVien) {
            $taiKhoan->sinhVien->anh_the_sinh_vien = $this->getFullImageUrl($taiKhoan->sinhVien->anh_the_sinh_vien);
            $taiKhoan->load(['sinhVien.chuyenNganhSanPham']);
            $taiKhoan->sinhVien->ten_chuyen_nganh = $taiKhoan->sinhVien->chuyenNganhSanPham->ten_nganh ?? null;
        }

        return response()->json($taiKhoan, 200);

    } catch (ValidationException $e) {
        Log::warning('Validation error during account update: ' . json_encode($e->errors()));
        return response()->json([
            'message' => 'Dữ liệu không hợp lệ.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Error updating account: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json([
            'message' => 'Đã có lỗi xảy ra khi cập nhật tài khoản.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Cập nhật thông tin lớp, chuyên ngành và ảnh thẻ sinh viên cho tài khoản.
     * Tự động kích hoạt tài khoản (trang_thai = 1) khi cập nhật thành công.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStudentProfile(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
                'lop' => 'required|string|max:50',
                'chuyen_nganh' => 'required|string|max:100',
                'anh_the_sinh_vien_file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $userId = $request->user_id;
            $lop = $request->lop;
            $chuyenNganh = $request->chuyen_nganh;
            $imageFile = $request->file('anh_the_sinh_vien_file');

            $taiKhoan = TaiKhoan::find($userId);

            if (!$taiKhoan) {
                return response()->json(['message' => 'Không tìm thấy tài khoản để cập nhật.'], 404);
            }
            
            DB::beginTransaction();

            // Tạo tên file duy nhất
            $imageName = $userId . '_student_card_' . time() . '.' . $imageFile->getClientOriginalExtension();
            // LƯU FILE VÀO storage/app/public/student_cards và LẤY ĐƯỜNG DẪN TƯƠNG ĐỐI
            $imagePath = $imageFile->storeAs('student_cards', $imageName, 'public');

            // Tìm hoặc tạo bản ghi SinhVien
            $sinhVien = SinhVien::firstOrNew(['id_tai_khoan' => $userId]);

            // Nếu sinh viên đã có ảnh thẻ cũ và file ảnh cũ tồn tại, hãy xóa nó
            if ($sinhVien->anh_the_sinh_vien && Storage::disk('public')->exists($sinhVien->anh_the_sinh_vien)) {
                Storage::disk('public')->delete($sinhVien->anh_the_sinh_vien);
            }

            // Cập nhật thông tin sinh viên với đường dẫn tương đối của ảnh
            $sinhVien->lop = $lop;
            $sinhVien->chuyen_nganh = $chuyenNganh;
            $sinhVien->anh_the_sinh_vien = $imagePath; // LƯU ĐƯỜNG DẪN TƯƠNG ĐỐI VÀO DB
            $sinhVien->save();

            // CẬP NHẬT TRẠNG THÁI TÀI KHOẢN KHI CẬP NHẬT THÔNG TIN SINH VIÊN THÀNH CÔNG
            // Nếu tài khoản đang chờ duyệt (0) hoặc bị khóa (2) và được cập nhật thông tin sinh viên,
            // thì chuyển sang trạng thái kích hoạt (1).
            // Nếu đã kích hoạt (1) thì giữ nguyên.
            if ($taiKhoan->trang_thai !== 1) { // Chỉ cập nhật nếu không phải đã kích hoạt
                $taiKhoan->trang_thai = 1; // Đặt trạng thái là 1 (kích hoạt)
                $taiKhoan->save(); // Lưu thay đổi trạng thái vào DB
            }
            
            DB::commit();

            // Lấy lại thông tin tài khoản và sinh viên để trả về với URL đầy đủ
            $taiKhoan->load('sinhVien');
            $taiKhoan->anh_dai_dien = $this->getFullImageUrl($taiKhoan->anh_dai_dien); // Cập nhật ảnh đại diện nếu có
            $studentCardUrl = null;
            if ($taiKhoan->sinhVien) {
                $studentCardUrl = $this->getFullImageUrl($taiKhoan->sinhVien->anh_the_sinh_vien);
            }

            return response()->json([
                'message' => 'Thông tin sinh viên đã được cập nhật thành công và tài khoản đã được kích hoạt!',
                'data' => [
                    'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                    'email' => $taiKhoan->email,
                    'ho_ten' => $taiKhoan->ho_ten,
                    'gioi_tinh' => $taiKhoan->gioi_tinh,
                    'so_dien_thoai' => $taiKhoan->so_dien_thoai,
                    'loai_tai_khoan' => $taiKhoan->loai_tai_khoan,
                    'trang_thai' => $taiKhoan->trang_thai,
                    'anh_dai_dien' => $taiKhoan->anh_dai_dien,
                    'sinh_vien' => [
                        'id_tai_khoan' => $taiKhoan->sinhVien->id_tai_khoan ?? null,
                        'lop' => $taiKhoan->sinhVien->lop ?? null,
                        'chuyen_nganh' => $taiKhoan->sinhVien->chuyen_nganh ?? null,
                        'anh_the_sinh_vien' => $studentCardUrl,
                    ],
                ]
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation error during student profile update (NO OCR): ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating student profile (NO OCR): ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật thông tin sinh viên. Vui lòng thử lại sau.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái tài khoản (ví dụ: kích hoạt, khóa).
     * Endpoint này thường là PUT/PATCH /api/tai-khoan/{id}/status
     *
     * @param Request $request
     * @param int $id ID tài khoản
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAccountStatus(Request $request, $id)
    {
        $taiKhoan = TaiKhoan::find($id);

        if (!$taiKhoan) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }

        try {
            $request->validate([
                'trang_thai' => 'required|integer|in:0,1,2', // Chỉ chấp nhận 0, 1, hoặc 2
            ]);

            $newStatus = $request->trang_thai;

            // Kiểm tra đặc biệt cho trường hợp Admin khóa tài khoản (chuyển sang 2)
            // Nếu tài khoản đang ở trạng thái 0 (chờ duyệt) và admin muốn khóa,
            // thì hành động khóa cần được thực hiện qua updateAccountStatus chứ không phải updateStudentProfile.
            // Điều này đảm bảo trạng thái 0 và 2 là riêng biệt.

            $taiKhoan->trang_thai = $newStatus;
            $taiKhoan->save();

            return response()->json([
                'message' => 'Trạng thái tài khoản đã được cập nhật thành công!',
                'data' => $taiKhoan, // Trả về thông tin tài khoản đã cập nhật
            ], 200);

        } catch (ValidationException $e) {
            Log::warning('Validation error during account status update: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating account status: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật trạng thái tài khoản.',
                'error_detail' => $e->getMessage()
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

        // Tải mối quan hệ SinhVien trước khi xóa để có thể xóa ảnh liên quan
        $taiKhoan->load('sinhVien'); 

        // Xóa các file ảnh liên quan khỏi storage
        if ($taiKhoan->anh_dai_dien) {
            Storage::disk('public')->delete($taiKhoan->anh_dai_dien);
        }
        
        if ($taiKhoan->sinhVien && $taiKhoan->sinhVien->anh_the_sinh_vien) {
            Storage::disk('public')->delete($taiKhoan->sinhVien->anh_the_sinh_vien);
        }

        $taiKhoan->delete(); // Xóa tài khoản khỏi database

        return response()->json(['message' => 'Tài khoản đã được xóa thành công.'], 204);
    }

        /**
     * Đổi mật khẩu cho người dùng.
     * Cần bảo vệ route này bằng middleware auth:sanctum.
     */


    public function changePassword(Request $request)
        {
            // dd($request->user());

            try {
                // 1. Validate the incoming request data
                $request->validate([
                    'user_id' => 'required|exists:TaiKhoan,id_tai_khoan',
                    'current_password' => 'required|string',
                    'new_password' => 'required|string|min:8|confirmed', // 'confirmed' checks against new_password_confirmation
                ]);

                $userId = $request->user_id;
                // 2. Sửa tên biến để khớp với key gửi từ Flutter (current_password)
                $currentPassword = $request->current_password; 
                $newPassword = $request->new_password;

                // 3. Find the user account by the provided user_id
                $taiKhoan = TaiKhoan::find($userId);

                // 4. NEW: Add an explicit null check for $taiKhoan
                // This prevents the "Attempt to read property ... on null" error
                if (!$taiKhoan) {
                    // Mặc dù validation 'exists' nên bắt lỗi này,
                    // nhưng nếu vì lý do nào đó mà validation không bắt được,
                    // chúng ta sẽ trả về lỗi 404 hoặc 400.
                    return response()->json(['message' => 'Tài khoản không tồn tại hoặc không tìm thấy.'], 404);
                }

                // 5. Security check: Ensure the authenticated user is changing their own password
                // This is crucial to prevent one user from changing another's password.
                // Dòng này (tức dòng 646 của bạn) giờ đã an toàn vì $taiKhoan chắc chắn không null
                if ($request->user()->id_tai_khoan != $taiKhoan->id_tai_khoan) {
                    return response()->json(['message' => 'Bạn không có quyền đổi mật khẩu cho tài khoản này.'], 403);
                }

                // 6. Verify the current password
                if (!Hash::check($currentPassword, $taiKhoan->mat_khau)) {
                    throw ValidationException::withMessages([
                        'current_password' => ['Mật khẩu hiện tại không chính xác.'],
                    ]);
                }

                // 7. Update the password with the new hashed password
                $taiKhoan->mat_khau = Hash::make($newPassword);
                $taiKhoan->save();

                return response()->json([
                    'message' => 'Mật khẩu của bạn đã được đổi thành công!',
                ], 200);

            } catch (ValidationException $e) {
                // Handle validation errors
                return response()->json([
                    'message' => 'Dữ liệu đầu vào không hợp lệ.',
                    'errors' => $e->errors(),
                ], 422);
            } catch (\Exception $e) {
                // Catch any other unexpected exceptions
                Log::error('Error changing password: ' . $e->getMessage(), ['exception' => $e]);
                return response()->json([
                    'message' => 'Đã có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại sau.',
                    'error_detail' => env('APP_DEBUG') ? $e->getMessage() : null, // Show detail in debug mode
                ], 500);
            }
        }
}
