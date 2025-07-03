<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaiKhoan;
use App\Models\ThongBao; // Đảm bảo đã import model ThongBao
use Illuminate\Support\Facades\Log; // Import Log facade để ghi log lỗi/thông tin

class ThongBaoController extends Controller
{
    /**
     * Gửi thông báo trạng thái tài khoản.
     * Đây là một API endpoint, nhận Request từ client hoặc hệ thống.
     *
     * @param Request $request Chứa 'id_tai_khoan' và 'trang_thai'.
     * @return \Illuminate\Http\JsonResponse Phản hồi JSON về trạng thái gửi thông báo.
     */
    // Gửi thông báo trạng thái tài khoản
    public function guiThongBaoTaiKhoan(Request $request)
    {
        $request->validate([
            'id_tai_khoan' => 'required|exists:TaiKhoan,id_tai_khoan',
            'trang_thai' => 'required|integer',
        ]);

        $taiKhoan = TaiKhoan::findOrFail($request->id_tai_khoan);

        $noiDung = match ($request->trang_thai) {
            1 => 'Tài khoản của bạn đã có thể hoạt động.',
            2 => 'Tài khoản của bạn đã bị khóa vì lí do chính sách.',
            default => null
        };

        if ($noiDung) {
            // Tạo thông báo với noi_dung và da_doc mặc định là 0
            ThongBao::create([
                'id_tai_khoan' => $taiKhoan->id_tai_khoan,
                'noi_dung' => $noiDung,
                'thoi_gian_tao' => now(),
                'da_doc' => 0 // Mặc định là chưa đọc
            ]);

            return response()->json(['message' => 'Gửi thông báo thành công']);
        }

        return response()->json(['message' => 'Trạng thái không hợp lệ'], 400);
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
     * Phương thức này được thiết kế để được gọi từ các controller khác (ví dụ: BaoCaoController)
     * khi có sự kiện liên quan đến báo cáo cần gửi thông báo đến người dùng.
     *
     * @param string $loaiThongBao Loại thông báo báo cáo cụ thể (e.g., 'go_bai_viet_chu_bai_viet', 'go_bai_viet_nguoi_bao_cao', 'tu_choi_bao_cao_nguoi_bao_cao').
     * @param int $idTaiKhoanNhan ID của tài khoản sẽ nhận thông báo.
     * @param array $data Mảng chứa dữ liệu bổ sung cần thiết cho nội dung thông báo (ví dụ: 'tieu_de_bai_viet', 'id_bai_dang', 'id_bao_cao').
     * @return bool True nếu thông báo được tạo và lưu thành công, False nếu có lỗi hoặc loại thông báo không hợp lệ.
     */
    public function guiThongBaoBaoCao(string $loaiThongBao, int $idTaiKhoanNhan, array $data = []): bool
    {
        $tieuDe = 'Thông báo từ hệ thống quản lý'; // Tiêu đề mặc định
        $noiDung = '';
        $lienKet = null; // Mặc định không có liên kết
        // Giữ lại loại thông báo ban đầu để truyền vào hàm helper
        $actualLoaiThongBao = $loaiThongBao; 

        switch ($loaiThongBao) {
            case 'go_bai_viet_chu_bai_viet':
                $tieuDeBaiViet = $data['tieu_de_bai_viet'] ?? 'một bài viết của bạn';
                $idBaiDang = $data['id_bai_dang'] ?? null;
                $tieuDe = 'Bài đăng của bạn đã bị gỡ';
                $noiDung = "Bài đăng \"{$tieuDeBaiViet}\" của bạn đã bị gỡ vì vi phạm chính sách của chúng tôi. Vui lòng xem xét lại quy định cộng đồng.";
                if ($idBaiDang) {
                    $lienKet = '/bai-dang/' . $idBaiDang . '?status=removed'; // Ví dụ: Liên kết đến trang chi tiết bài đăng đã gỡ
                }
                break;

            case 'go_bai_viet_nguoi_bao_cao':
                $idBaoCao = $data['id_bao_cao'] ?? null;
                $tieuDe = 'Báo cáo của bạn đã được xử lý';
                $noiDung = "Cảm ơn bạn đã báo cáo. Chúng tôi đã xem xét và gỡ bài đăng vi phạm mà bạn đã báo cáo. Đóng góp của bạn rất quan trọng!";
                if ($idBaoCao) {
                    $lienKet = '/bao-cao/' . $idBaoCao . '?status=resolved'; // Ví dụ: Liên kết đến trạng thái báo cáo
                }
                break;

            case 'tu_choi_bao_cao_nguoi_bao_cao':
                $idBaoCao = $data['id_bao_cao'] ?? null;
                $tieuDe = 'Báo cáo của bạn đã được xem xét';
                $noiDung = "Cảm ơn bạn đã báo cáo. Chúng tôi đã xem xét báo cáo của bạn và quyết định không gỡ bài đăng vì nó không vi phạm chính sách của chúng tôi.";
                if ($idBaoCao) {
                    $lienKet = '/bao-cao/' . $idBaoCao . '?status=rejected'; // Ví dụ: Liên kết đến trạng thái báo cáo
                }
                break;

            default:
                // Ghi log cảnh báo nếu có loại thông báo không mong muốn được gọi
                Log::warning('Loại thông báo báo cáo không hợp lệ được gọi: ' . $loaiThongBao);
                return false; // Trả về false để báo hiệu lỗi
        }

        // GỌI HÀM HELPER VỚI ĐỦ CÁC THAM SỐ CẦN THIẾT
        return $this->createAndSaveNotification(
            $idTaiKhoanNhan,
            $tieuDe,             // Đã sửa: truyền tieuDe
            $noiDung,
            $actualLoaiThongBao, // Đã sửa: truyền loaiThongBao
            $lienKet
        );
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
}