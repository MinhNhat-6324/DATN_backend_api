<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaoCao;
use App\Models\BaiDang; // Đảm bảo đã import model BaiDang
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; // Thêm dòng này để sử dụng transaction
use App\Models\TaiKhoan; // <<< THÊM DÒNG NÀY ĐỂ CÓ THỂ EAGER LOAD QUAN HỆ
use App\Http\Controllers\Api\ThongBaoController; // <<< THÊM DÒNG NÀY ĐỂ IMPORT ThongBaoController

class BaoCaoController extends Controller
{
    // <<< THÊM PHẦN CONSTRUCTOR NÀY ĐỂ INJECT ThongBaoController
    protected $thongBaoController;

    public function __construct(ThongBaoController $thongBaoController)
    {
        $this->thongBaoController = $thongBaoController;
    }
    // >>> KẾT THÚC PHẦN THÊM CONSTRUCTOR

    // GET /api/bao-cao
    public function index()
    {
        $baoCaos = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->get();
        return response()->json($baoCaos);
    }

    // POST /api/bao-cao
    public function store(Request $request)
    {
        try {
            $request->validate([
                'ma_bai_dang' => 'required|integer|exists:BaiDang,id_bai_dang',
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                // Trạng thái khi tạo mới luôn là 'dang_cho' hoặc có thể bỏ validation 'trang_thai' ở đây
                // vì nó sẽ được set mặc định bên dưới
            ]);

            $baoCao = BaoCao::create([
                'ma_bai_dang' => $request->ma_bai_dang,
                'id_tai_khoan_bao_cao' => $request->id_tai_khoan_bao_cao,
                'ly_do' => $request->ly_do,
                'mo_ta_them' => $request->mo_ta_them,
                'trang_thai' => 'dang_cho', // Mặc định khi tạo báo cáo là 'dang_cho'
                'thoi_gian_bao_cao' => now(),
            ]);

            return response()->json($baoCao, 201);
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

    // POST /api/bao-cao/bai-dang/{id_bai_dang}
    // Giữ nguyên hoặc điều chỉnh tùy theo cách bạn muốn user tạo báo cáo
    public function postByBaiDang(Request $request, $id_bai_dang)
    {
        try {
            $request->validate([
                'id_tai_khoan_bao_cao' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
            ]);

            $baiDang = BaiDang::find($id_bai_dang);
            if (!$baiDang) {
                return response()->json(['message' => 'Bài đăng không tồn tại.'], 404);
            }

            $baoCao = BaoCao::create([
                'ma_bai_dang' => $id_bai_dang,
                'id_tai_khoan_bao_cao' => $request->id_tai_khoan_bao_cao,
                'ly_do' => $request->ly_do,
                'mo_ta_them' => $request->mo_ta_them,
                'trang_thai' => 'dang_cho', // Mặc định khi tạo báo cáo là 'dang_cho'
                'thoi_gian_bao_cao' => now(),
            ]);

            return response()->json($baoCao, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tạo báo cáo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/bao-cao/{id}
    public function show($id)
    {
        $baoCao = BaoCao::with(['baiDang', 'taiKhoanBaoCao'])->find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        return response()->json($baoCao);
    }

    // PUT /api/bao-cao/{id} - Có thể giữ lại cho các cập nhật chung, nhưng thường không dùng để cập nhật trạng thái
    public function update(Request $request, $id)
    {
        $baoCao = BaoCao::find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        // Báo cáo đã duyệt không thể cập nhật (nếu bạn muốn enforce điều này)
        if ($baoCao->trang_thai === 'da_duyet') {
            return response()->json(['message' => 'Báo cáo đã được duyệt và không thể cập nhật.'], 403); // Forbidden
        }

        try {
            $request->validate([
                'ly_do' => 'nullable|string|max:255',
                'mo_ta_them' => 'nullable|string',
                // Loai bo 'trang_thai' khoi day neu cac phuong thuc rieng biet se xu ly trang thai
                // 'trang_thai' => 'string|in:dang_cho,da_duyet', // Cập nhật các trạng thái hợp lệ
            ]);

            $baoCao->update($request->only(['ly_do', 'mo_ta_them'])); // Chỉ cập nhật các trường này

            return response()->json($baoCao);
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

    // <<<<<< PHƯƠNG THỨC MỚI: GỠ BÀI ĐĂNG >>>>>>
    // POST /api/bao-cao/{id}/go-bai-dang
    public function goBaiDang(Request $request, $id)
    {
        // <<< SỬA ĐỔI EAGER LOADING ĐỂ LẤY THÔNG TIN TaiKhoan CỦA BaiDang
        $baoCao = BaoCao::with('baiDang.taiKhoan')->find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        // Chỉ xử lý nếu báo cáo đang ở trạng thái 'dang_cho'
        if ($baoCao->trang_thai !== 'dang_cho') {
            return response()->json([
                'message' => 'Báo cáo này đã được xử lý (trạng thái: ' . $baoCao->trang_thai . ').'
            ], 400); // Bad Request
        }

        DB::beginTransaction(); // Bắt đầu transaction
        try {
            // 1. Cập nhật trạng thái bài đăng thành 'vi_pham'
            if ($baoCao->baiDang) {
                $baoCao->baiDang->update(['trang_thai' => 'vi_pham']);

                // <<< GỬI THÔNG BÁO ĐẾN CHỦ BÀI VIẾT (nếu có)
                if ($baoCao->baiDang->taiKhoan) {
                    $this->thongBaoController->guiThongBaoBaoCao(
                        'go_bai_viet_chu_bai_viet',
                        $baoCao->baiDang->taiKhoan->id_tai_khoan,
                        ['tieu_de_bai_viet' => $baoCao->baiDang->tieu_de]
                    );
                }
                // >>> KẾT THÚC GỬI THÔNG BÁO ĐẾN CHỦ BÀI VIẾT

            } else {
                // Xử lý trường hợp bài đăng đã bị xóa trước khi báo cáo được xử lý
                // Có thể log lại hoặc trả về lỗi nếu bài đăng là bắt buộc
                // Tuy nhiên, theo yêu cầu thì nếu bài đăng không tồn tại, chỉ cần duyệt báo cáo
                \Log::warning('Báo cáo ID ' . $id . ' liên kết với bài đăng không tồn tại khi gỡ bài.');
            }

            // 2. Cập nhật trạng thái báo cáo thành 'da_xu_ly'
            $baoCao->update(['trang_thai' => 'da_xu_ly']);

            // <<< GỬI THÔNG BÁO CÁM ƠN ĐẾN NGƯỜI BÁO CÁO
            $this->thongBaoController->guiThongBaoBaoCao(
                'go_bai_viet_nguoi_bao_cao',
                $baoCao->id_tai_khoan_bao_cao
            );
            // >>> KẾT THÚC GỬI THÔNG BÁO CÁM ƠN

            DB::commit(); // Hoàn tất transaction

            return response()->json([
                'message' => 'Bài đăng đã được gỡ và báo cáo đã được duyệt.',
                'bao_cao' => $baoCao->load('baiDang') // Tải lại bài đăng để có trạng thái mới nhất
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Hoàn tác transaction nếu có lỗi
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi gỡ bài đăng và duyệt báo cáo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // <<<<<< PHƯƠNG THỨC MỚI: TỪ CHỐI BÁO CÁO >>>>>>
    // POST /api/bao-cao/{id}/tu-choi
    public function tuChoiBaoCao(Request $request, $id)
    {
        $baoCao = BaoCao::with('baiDang')->find($id); // Vẫn eager load baiDang dù không cập nhật, để có thông tin cần nếu sau này mở rộng

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        // Chỉ xử lý nếu báo cáo đang ở trạng thái 'dang_cho'
        if ($baoCao->trang_thai !== 'dang_cho') {
            return response()->json([
                'message' => 'Báo cáo này đã được xử lý (trạng thái: ' . $baoCao->trang_thai . ').'
            ], 400); // Bad Request
        }

        DB::beginTransaction(); // Bắt đầu transaction
        try {
            // 1. Bài đăng giữ nguyên trạng thái (Không làm gì với baiDang->trang_thai)
            // if ($baoCao->baiDang) {
            //     // Không cần cập nhật trạng thái bài đăng ở đây
            // }

            // 2. Cập nhật trạng thái báo cáo thành 'da_xu_ly'
            $baoCao->update(['trang_thai' => 'da_xu_ly']);

            // <<< GỬI THÔNG BÁO CÁM ƠN ĐẾN NGƯỜI BÁO CÁO
            $this->thongBaoController->guiThongBaoBaoCao(
                'tu_choi_bao_cao_nguoi_bao_cao',
                $baoCao->id_tai_khoan_bao_cao
            );
            // >>> KẾT THÚC GỬI THÔNG BÁO CÁM ƠN

            DB::commit(); // Hoàn tất transaction

            return response()->json([
                'message' => 'Báo cáo đã được từ chối và duyệt.',
                'bao_cao' => $baoCao
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Hoàn tác transaction nếu có lỗi
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi từ chối báo cáo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // DELETE /api/bao-cao/{id}
    public function destroy($id)
    {
        $baoCao = BaoCao::find($id);

        if (!$baoCao) {
            return response()->json(['message' => 'Không tìm thấy báo cáo.'], 404);
        }

        // Không cho phép xóa báo cáo đã duyệt (tùy chọn)
        if ($baoCao->trang_thai === 'da_xy_ly') {
            return response()->json(['message' => 'Không thể xóa báo cáo đã được duyệt.'], 403);
        }

        $baoCao->delete();

        return response()->json(['message' => 'Báo cáo đã được xóa thành công.'], 204);
    }
}