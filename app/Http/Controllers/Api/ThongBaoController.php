<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\ThongBao; // Đảm bảo đã import model ThongBao
use Illuminate\Support\Facades\Log; // Import Log facade để ghi log lỗi/thông tin

class ThongBaoController extends Controller
{
    // Cập nhật trạng thái trạng thái tài khoản và thông báo kèm lý do
    public function capNhatTrangThaiTaiKhoan(Request $request)
    {
        $request->validate([
            'id_tai_khoan' => 'required|exists:TaiKhoan,id_tai_khoan',
            'trang_thai' => 'required|integer',
            'ly_do' => 'required_if:trang_thai,2|string|max:255',
        ]);

        try {
            $taiKhoan = TaiKhoan::findOrFail($request->id_tai_khoan);

            // 1. Cập nhật trạng thái
            $taiKhoan->trang_thai = $request->trang_thai;
            $taiKhoan->save();

            // 2. Xác định nội dung thông báo
            if ($request->trang_thai == 1) {
                $tieuDe = 'Tài khoản hoạt động trở lại';
                $noiDung = 'Tài khoản của bạn đã có thể hoạt động.';
                $loaiThongBao = 'tai_khoan_mo_khoa';
            } elseif ($request->trang_thai == 2) {
                $tieuDe = 'Tài khoản đã bị khóa';
                $noiDung = 'Tài khoản của bạn đã bị khóa. Lý do: ' . $request->ly_do;
                $loaiThongBao = 'khoa_tai_khoan';
            } else {
                return response()->json(['message' => 'Trạng thái không hợp lệ'], 400);
            }

            // 3. Gửi thông báo
            $this->createAndSaveNotification(
                $taiKhoan->id_tai_khoan,
                $tieuDe,
                $noiDung,
                $loaiThongBao
            );

            return response()->json(['message' => 'Cập nhật trạng thái và gửi thông báo thành công']);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật trạng thái tài khoản: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi khi cập nhật.', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Lấy danh sách thông báo của một tài khoản cụ thể.
     *
     * @param int $idTaiKhoan ID của tài khoản cần lấy thông báo.
     * @return \Illuminate\Http\JsonResponse Danh sách thông báo hoặc thông báo lỗi.
     */
    public function layThongBaoTheoTaiKhoan($idTaiKhoan)
    {
        try {
            // Lấy thông báo theo idTaiKhoan và sắp xếp theo 'thoi_gian_gui' giảm dần (mới nhất lên đầu)
            $thongBaos = ThongBao::where('id_tai_khoan', $idTaiKhoan)
                                 ->orderBy('id_thong_bao', 'desc')
                                 ->get();

            // Trả về danh sách thông báo
            return response()->json($thongBaos);

        } catch (\Exception $e) {
            // Ghi log lỗi để dễ dàng debug và theo dõi
            Log::error('Lỗi khi lấy thông báo theo tài khoản ID: ' . $idTaiKhoan . '. Lỗi: ' . $e->getMessage());
            return response()->json(['message' => 'Không thể lấy danh sách thông báo. Vui lòng thử lại sau.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gửi thông báo liên quan đến việc xử lý báo cáo.
     */
    public function guiThongBaoBaoCao(string $loaiThongBao, int $idTaiKhoanNhan, array $data = []): bool
{
    $tieuDe = 'Thông báo từ hệ thống quản lý'; // Tiêu đề mặc định
    $noiDung = '';
    $lienKet = null;

    try {
        switch ($loaiThongBao) {
            case 'go_bai_viet_chu_bai_viet':
                // Chủ bài viết bị gỡ
                $tieuDeBaiViet = $data['tieu_de_bai_viet'] ?? 'bài viết của bạn';
                $idBaiDang = $data['id_bai_dang'] ?? null;
                $lyDoGo = $data['ly_do_go'] ?? null;

                $tieuDe = 'Bài đăng của bạn đã bị gỡ';
                $noiDung = "Bài đăng \"{$tieuDeBaiViet}\" của bạn đã bị gỡ vì vi phạm chính sách.";
                
                if ($lyDoGo) {
                    $noiDung .= " Lý do: {$lyDoGo}.";
                }

                $noiDung .= " Vui lòng xem lại quy định cộng đồng.";
                
                if ($idBaiDang) {
                    $lienKet = '/bai-dang/' . $idBaiDang . '?status=removed';
                }

                Log::info("Thông báo gỡ bài gửi đến {$idTaiKhoanNhan}: {$noiDung}");
                break;

            case 'go_bai_viet_nguoi_bao_cao':
                // Người báo cáo được thông báo xử lý thành công
                $idBaoCao = $data['id_bao_cao'] ?? null;
                $tieuDe = 'Báo cáo của bạn đã được xử lý';
                $noiDung = "Cảm ơn bạn đã báo cáo. Chúng tôi đã xem xét và gỡ bài đăng vi phạm mà bạn đã báo cáo. Đóng góp của bạn rất quan trọng!";
                
                if ($idBaoCao) {
                    $lienKet = '/bao-cao/' . $idBaoCao . '?status=resolved';
                }

                Log::info("Thông báo xử lý báo cáo gửi đến {$idTaiKhoanNhan}");
                break;

            case 'tu_choi_bao_cao_nguoi_bao_cao':
                // Người báo cáo được thông báo từ chối
                $idBaoCao = $data['id_bao_cao'] ?? null;
                $tieuDe = 'Báo cáo của bạn đã được xem xét';
                $noiDung = "Cảm ơn bạn đã báo cáo. Chúng tôi đã xem xét báo cáo của bạn và quyết định không gỡ bài đăng vì nó không vi phạm chính sách.";
                
                if ($idBaoCao) {
                    $lienKet = '/bao-cao/' . $idBaoCao . '?status=rejected';
                }

                Log::info("Thông báo từ chối báo cáo gửi đến {$idTaiKhoanNhan}");
                break;

            default:
                Log::warning("Gọi guiThongBaoBaoCao với loại thông báo không hợp lệ: {$loaiThongBao}");
                return false; // Không gửi thông báo
        }

        // GỌI HELPER LƯU VÀ GỬI THÔNG BÁO
        return $this->createAndSaveNotification(
            $idTaiKhoanNhan,
            $tieuDe,
            $noiDung,
            $loaiThongBao,
            $lienKet
        );

    } catch (\Exception $e) {
        Log::error("Lỗi guiThongBaoBaoCao: " . $e->getMessage());
        return false;
    }
}


    /**
     * ĐÁNH DẤU TRẠNG THÁI THÔNG BÁO (ĐÃ ĐỌC HOẶC CHƯA ĐỌC).
     *
     * @param Request $request Chứa tùy chọn 'da_doc' (mặc định là 1 nếu không gửi).
     * @param int $idThongBao ID của thông báo cần cập nhật.
     * @return \Illuminate\Http\JsonResponse Phản hồi JSON về trạng thái cập nhật.
     */
    public function markAsRead(Request $request, $idThongBao)
    {
        $thongBao = ThongBao::find($idThongBao);

        if (!$thongBao) {
            return response()->json(['message' => 'Thông báo không tồn tại'], 404);
        }

        // Cập nhật trường da_doc. Lấy giá trị từ request, mặc định là 1 (đã đọc) nếu không gửi.
        // Đây cũng có thể dùng để đánh dấu lại là chưa đọc (0) nếu cần.
        $thongBao->da_doc = $request->input('da_doc', 1);

        try {
            $thongBao->save();
            return response()->json(['message' => 'Cập nhật trạng thái thông báo thành công', 'thong_bao' => $thongBao]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật trạng thái thông báo ID: ' . $idThongBao . '. Lỗi: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi khi cập nhật trạng thái thông báo. Vui lòng thử lại.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * HÀM HELPER NỘI BỘ: Tạo và lưu thông báo vào cơ sở dữ liệu.
     * Phương thức này là `private` và chỉ được gọi bởi các phương thức khác trong controller này
     * để đảm bảo tính nhất quán và tái sử dụng code khi tạo thông báo.
     *
     * @param int $idTaiKhoan ID của tài khoản nhận thông báo.
     * @param string $tieuDe Tiêu đề của thông báo.
     * @param string $noiDung Nội dung chi tiết của thông báo.
     * @param string $loaiThongBao Loại thông báo (e.g., 'tai_khoan_cap_nhat', 'bai_dang_vi_pham', 'bao_cao_tu_choi').
     * @param string|null $lienKet Đường dẫn URL hoặc liên kết liên quan đến thông báo (có thể null).
     * @return bool True nếu thông báo được tạo và lưu thành công, False nếu có lỗi xảy ra.
     */
    private function createAndSaveNotification(
        int $idTaiKhoan,
        string $tieuDe,
        string $noiDung,
        string $loaiThongBao,
        ?string $lienKet = null
    ): bool {
        try {
            ThongBao::create([
                'id_tai_khoan' => $idTaiKhoan,
                'tieu_de' => $tieuDe,
                'noi_dung' => $noiDung,
                'loai_thong_bao' => $loaiThongBao,
                'lien_ket' => $lienKet,
                'da_doc' => false, // Mặc định là chưa đọc khi mới tạo
                'thoi_gian_gui' => now(), // Sử dụng thời gian hiện tại của server
            ]);
            // Ghi log thông tin khi thông báo được tạo thành công
            Log::info("Đã tạo thông báo '$tieuDe' (Loại: $loaiThongBao) cho tài khoản ID: $idTaiKhoan");
            return true;
        } catch (\Exception $e) {
            // Ghi log lỗi nếu quá trình tạo thông báo thất bại
            Log::error("Lỗi khi tạo thông báo cho tài khoản ID: $idTaiKhoan. Lỗi: " . $e->getMessage());
            return false;
        }
    }

    public function guiYeuCauMoKhoa(Request $request)
{
    $request->validate([
        'id_tai_khoan' => 'required|exists:TaiKhoan,id_tai_khoan',
        'noi_dung' => 'nullable|string|max:255'
    ]);

    try {
        $taiKhoan = TaiKhoan::findOrFail($request->id_tai_khoan);

        if ($taiKhoan->trang_thai != 2) {
            return response()->json(['message' => 'Tài khoản này không bị khóa, không cần gửi yêu cầu.'], 400);
        }

        $tieuDe = 'Yêu cầu mở khóa tài khoản';
        $noiDung = 'Sinh viên ' . $taiKhoan->ho_ten .' có email là '.$taiKhoan->email. ' đã gửi yêu cầu mở khóa tài khoản.';
        if ($request->filled('noi_dung')) {
            $noiDung .= ' Nội dung: ' . $request->noi_dung;
        }


        // Lấy tất cả admin
        $admins = TaiKhoan::where('loai_tai_khoan', 1)->get();

        foreach ($admins as $admin) {
            $this->createAndSaveNotification(
                $admin->id_tai_khoan,
                $tieuDe,
                $noiDung,
                'yeu_cau_mo_khoa',
                '/admin/tai-khoan/' . $taiKhoan->id_tai_khoan
            );
        }

        return response()->json(['message' => 'Yêu cầu mở khóa đã được gửi đến quản trị viên.']);

    } catch (\Exception $e) {
        \Log::error('Lỗi gửi yêu cầu mở khóa: ' . $e->getMessage());
        return response()->json(['message' => 'Không thể gửi yêu cầu lúc này.', 'error' => $e->getMessage()], 500);
    }
}

}