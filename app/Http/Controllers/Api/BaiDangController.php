<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaiDang;
use App\Models\AnhBaiDang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
class BaiDangController extends Controller
{

    private function capNhatTrangThaiQuaHan()
    {
        $baiDangs = BaiDang::all();

        foreach ($baiDangs as $baiDang) {
            $ngayDang = \Carbon\Carbon::parse($baiDang->ngay_dang);
            $soNgay = $ngayDang->diffInDays(now());

            if ($baiDang->trang_thai === 'san_sang' && $soNgay > 3) {
                // Sau 3 ngày => chuyển sang 'quá hạn'
                $baiDang->trang_thai = 'qua_han';
                $baiDang->save();
            }

            if ($baiDang->trang_thai === 'qua_han' && $soNgay > 4) {
                // Sau 4 ngày => xoá bài đăng
                // Xoá ảnh vật lý
                foreach ($baiDang->anhBaiDang as $anh) {
                    $relativePath = str_replace('/storage/', '', $anh->duong_dan);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
                }

                // Xoá bản ghi ảnh
                \App\Models\AnhBaiDang::where('id_bai_dang', $baiDang->id_bai_dang)->delete();

                // Xoá bài đăng
                $baiDang->delete();
            }
        }
    }



    /**
     * GET /api/bai-dang
     */
    public function index()
    {
        $this->capNhatTrangThaiQuaHan($baiDangs);
        $baiDangs = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
                'lop_chuyen_nganh', 
                'nam_xuat_ban',
            ])
            ->where('trang_thai', 'san_sang')
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
       

    }

    /**
     * GET /api/bai-dang/nganh/{id}
     */
    public function getByNganh($id_nganh)
    {
        $this->capNhatTrangThaiQuaHan();
        $baiDang = BaiDang::with([
            'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
            'chuyenNganhSanPham:id_nganh,ten_nganh'
        ])
        ->select([
            'id_bai_dang',
            'id_tai_khoan',
            'tieu_de',
            'do_moi',
            'trang_thai',
            'ngay_dang',
            'id_loai',
            'id_nganh',
            'lop_chuyen_nganh', 
            'nam_xuat_ban',
        ])
        ->where('id_nganh', $id_nganh)
        ->where('trang_thai', 'san_sang')
        ->orderByDesc('ngay_dang')
        ->get();

        return response()->json($baiDang);
    }

    /**
     * GET /api/bai-dang/{id}
     */
    public function show($id)
{
    $this->capNhatTrangThaiQuaHan();
    $baiDang = BaiDang::with([
            'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
            'chuyenNganhSanPham:id_nganh,ten_nganh',
            'loaiSanPham:id_loai,ten_loai'
        ])
        ->select([
            'id_bai_dang',
            'id_tai_khoan',
            'tieu_de',
            'do_moi',
            'trang_thai',
            'ngay_dang',
            'id_loai',
            'id_nganh',
            'lop_chuyen_nganh', 
            'nam_xuat_ban',
        ])
        ->find($id);

    if (!$baiDang) {
        return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
    }

    return response()->json($baiDang);
}


    /**
     * GET /api/bai-dang/nganh/{id_nganh}/loai/{id_loai}
     */
    public function getByNganhVaLoai($id_nganh, $id_loai)
    {
        $this->capNhatTrangThaiQuaHan();
        $baiDangQuery = BaiDang::with([
            'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
            'chuyenNganhSanPham:id_nganh,ten_nganh'
        ])
        ->select([
            'id_bai_dang',
            'id_tai_khoan',
            'tieu_de',
            'do_moi',
            'trang_thai',
            'ngay_dang',
            'id_loai',
            'id_nganh',
            'lop_chuyen_nganh', 
            'nam_xuat_ban',
        ])
        ->where('id_nganh', $id_nganh)
        ->where('trang_thai', 'san_sang');

        if ($id_loai != -1) {
            $baiDangQuery->where('id_loai', $id_loai);
        }

        return response()->json($baiDangQuery->orderByDesc('ngay_dang')->get());
    }

    /**
     * GET /api/bai-dang/tieu-de/{tieu_de}
     */
    public function getByTieuDe($tieu_de)
    {
        $this->capNhatTrangThaiQuaHan();
        $baiDangs = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
                'lop_chuyen_nganh', 
                'nam_xuat_ban',
            ])
            ->where('tieu_de', 'LIKE', '%' . $tieu_de . '%')
            ->where('trang_thai', 'san_sang')
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    }

    /**
     * GET /api/bai-dang/loc/{id_nganh}/{id_loai}/{tieu_de}
     */
    public function locBaiDang($id_nganh, $id_loai, $tieu_de)
    {
        $this->capNhatTrangThaiQuaHan();
        $query = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
                'lop_chuyen_nganh', 
                'nam_xuat_ban',
            ])
            ->where('id_nganh', $id_nganh)
            ->where('trang_thai', 'san_sang'); 

        if ($id_loai != -1) {
            $query->where('id_loai', $id_loai);
        }

        if ($tieu_de !== '-' && $tieu_de !== '') {
            $query->where('tieu_de', 'LIKE', '%' . $tieu_de . '%');
        }

        return response()->json($query->orderByDesc('ngay_dang')->get());
    }

    /**
     * GET /api/bai-dang/loai/{id_loai}/tieu-de/{tieu_de}
     */
    public function locTheoLoaiVaTieuDe($id_loai, $tieu_de = null)
    {
        $this->capNhatTrangThaiQuaHan();
        $query = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
                'lop_chuyen_nganh', 
                'nam_xuat_ban',
            ])
            ->where('id_loai', $id_loai)
            ->where('trang_thai', 'san_sang');

        if ($tieu_de !== null && $tieu_de !== '' && $tieu_de !== '-') {
            $query->where('tieu_de', 'LIKE', '%' . $tieu_de . '%');
        }

        return response()->json($query->orderByDesc('ngay_dang')->get());
    }

    /**
     * GET /api/bai-dang/loai/{id_loai}
     */
    public function getByLoai($id_loai)
    {
        $this->capNhatTrangThaiQuaHan();
        $baiDangs = BaiDang::with([
                'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
                'chuyenNganhSanPham:id_nganh,ten_nganh'
            ])
            ->select([
                'id_bai_dang',
                'id_tai_khoan',
                'tieu_de',
                'do_moi',
                'trang_thai',
                'ngay_dang',
                'id_loai',
                'id_nganh',
                'lop_chuyen_nganh', 
                'nam_xuat_ban',
            ])
            ->where('id_loai', $id_loai)
            ->where('trang_thai', 'san_sang')
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    }

    /**
     * POST /api/bai-dang
     */
    public function store(Request $request)
    {

        $request->validate([
            'id_tai_khoan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
            'tieu_de' => 'required|string|max:255',
            'do_moi' => 'required|numeric|min:0|max:100',
            'id_loai' => 'required|integer|exists:LoaiSanPham,id_loai',
            'id_nganh' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
            'lop_chuyen_nganh' => 'required|string|in:CĐ Nghề,CĐ Ngành',
            'nam_xuat_ban' => 'required|integer|max:' . date('Y'),
            'hinh_anh' => 'nullable|array',
            'hinh_anh.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // ✅ 1. Danh sách từ khóa cấm
        $tuKhoaCam = [
            'sex', 'xxx', 'free', 'thuốc', 'hack', 'liên hệ', 'gái gọi',
            'sugar baby', 'rẻ nhất', 'trúng thưởng', 'mua ngay', 'đặt cọc',
            'phản động', 'viagra', 'lừa đảo'
        ];

        $tieuDe = Str::lower($request->tieu_de);

        foreach ($tuKhoaCam as $keyword) {
            if (Str::contains($tieuDe, $keyword)) {
                return response()->json([
                    'message' => "Tiêu đề chứa từ khóa không phù hợp: '$keyword'. Vui lòng kiểm tra lại."
                ], 422);
            }
        }

        // ✅ 2. Kiểm tra tiêu đề trùng trong ngày
        $tieuDeTrung = BaiDang::where('id_tai_khoan', $request->id_tai_khoan)
            ->whereDate('ngay_dang', Carbon::today())
            ->where('tieu_de', $request->tieu_de)
            ->exists();

        if ($tieuDeTrung) {
            return response()->json([
                'message' => 'Bạn đã đăng bài với tiêu đề này trong hôm nay. Vui lòng chọn tiêu đề khác để tránh spam.'
            ], 429);
        }

       

        // ✅ 3. Tạo bài đăng
        $baiDang = BaiDang::create([
            'id_tai_khoan' => $request->id_tai_khoan,
            'tieu_de' => $request->tieu_de,
            'do_moi' => $request->do_moi,
            'id_loai' => $request->id_loai,
            'id_nganh' => $request->id_nganh,
            'lop_chuyen_nganh' => $request->lop_chuyen_nganh,
            'nam_xuat_ban' => $request->nam_xuat_ban,
            'ngay_dang' => Carbon::now(),
            'trang_thai' => 'san_sang',
        ]);

        // ✅ 4. Lưu ảnh nếu có
        if ($request->hasFile('hinh_anh')) {
            foreach ($request->file('hinh_anh') as $index => $image) {
                $filename = uniqid('img_') . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('hinh_anh_bai_dang', $filename, 'public');
                $url = '/storage/hinh_anh_bai_dang/' . $filename;

                AnhBaiDang::create([
                    'id_bai_dang' => $baiDang->id_bai_dang,
                    'duong_dan' => $url,
                    'thu_tu' => $index + 1,
                ]);
            }
        }

        return response()->json([
            'message' => 'Bài đăng đã được tạo thành công.',
            'id_bai_dang' => $baiDang->id_bai_dang
        ], 201);
    }

    /**
 * GET /api/bai-dang/nguoi-dung/{id_tai_khoan}
 */
public function getByTaiKhoan($id_tai_khoan)
{
    $this->capNhatTrangThaiQuaHan();
    $baiDangs = BaiDang::with([
            'anhBaiDang:id_bai_dang,duong_dan,thu_tu',
            'chuyenNganhSanPham:id_nganh,ten_nganh'
        ])
        ->select([
            'id_bai_dang',
            'id_tai_khoan',
            'tieu_de',
            'do_moi',
            'trang_thai',
            'ngay_dang',
            'id_loai',
            'id_nganh',
            'lop_chuyen_nganh', 
            'nam_xuat_ban',
        ])
        ->where('id_tai_khoan', $id_tai_khoan)
        ->orderByDesc('ngay_dang')
        ->get();

    return response()->json($baiDangs);
}
public function update(Request $request, $id)
{
    
    $baiDang = BaiDang::findOrFail($id);

    $baiDang->tieu_de = $request->input('tieu_de');
   // $baiDang->do_moi = $request->input('do_moi');
    $baiDang->id_loai = $request->input('id_loai');
    $baiDang->id_nganh = $request->input('id_nganh');
    $baiDang->do_moi = $request->input('do_moi');
    $baiDang->nam_xuat_ban = $request->input('nam_xuat_ban');
    $baiDang->lop_chuyen_nganh = $request->input('lop_chuyen_nganh'); 
    $baiDang->save();

    // ✅ Xoá ảnh cũ nếu cần
    if ($request->has('hinh_anh_can_xoa')) {
        foreach ($request->hinh_anh_can_xoa as $fileName) {
            $path = 'hinh_anh_bai_dang/' . $fileName;

            // Xoá file vật lý
            Storage::disk('public')->delete($path);

            // Xoá bản ghi trong DB
            AnhBaiDang::where('id_bai_dang', $baiDang->id_bai_dang)
                ->where('duong_dan', '/storage/' . $path)
                ->delete();
        }
    }

    // ✅ Thêm ảnh mới
    if ($request->hasFile('hinh_anh')) {
        foreach ($request->file('hinh_anh') as $index => $image) {
            $path = $image->store('hinh_anh_bai_dang', 'public');
            AnhBaiDang::create([
                'id_bai_dang' => $baiDang->id_bai_dang,
                'duong_dan' => '/storage/' . $path,
                'thu_tu' => $index + 1,
            ]);
        }
    }

    return response()->json(['message' => 'Cập nhật thành công'], 200);
}
/**
 * DELETE /api/bai-dang/{id}
 */
public function destroy($id)
{
    $baiDang = BaiDang::find($id);

    if (!$baiDang) {
        return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
    }

    // ✅ Xoá ảnh vật lý và bản ghi ảnh liên quan
    $anhBaiDangs = AnhBaiDang::where('id_bai_dang', $baiDang->id_bai_dang)->get();

    foreach ($anhBaiDangs as $anh) {
        // Lấy đường dẫn tương đối từ URL
        $relativePath = str_replace('/storage/', '', $anh->duong_dan);

        // Xoá file trong thư mục public
        Storage::disk('public')->delete($relativePath);
    }

    // Xoá bản ghi ảnh
    AnhBaiDang::where('id_bai_dang', $baiDang->id_bai_dang)->delete();

    // Xoá bài đăng
    $baiDang->delete();

    return response()->json(['message' => 'Bài đăng đã được xoá.'], 200);
}
public function doiTrangThai(Request $request, $id)
{

    $baiDang = BaiDang::find($id);
    if (!$baiDang) {
        return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
    }

    $trangThaiMoi = $request->input('trang_thai');
    if (!in_array($trangThaiMoi, ['san_sang', 'da_cho_tang'])) {
        return response()->json(['message' => 'Trạng thái không hợp lệ.'], 400);
    }

    $baiDang->trang_thai = $trangThaiMoi;
    $baiDang->save();

    return response()->json(['message' => 'Cập nhật trạng thái thành công.']);
}
    public function repost($id)
{
    $baiDangCu = BaiDang::with('anhBaiDang')->find($id);

    if (!$baiDangCu) {
        return response()->json(['message' => 'Không tìm thấy bài đăng.'], 404);
    }

    // ✅ Tạo bài đăng mới (bản sao)
    $baiDangMoi = BaiDang::create([
        'id_tai_khoan' => $baiDangCu->id_tai_khoan,
        'tieu_de' => $baiDangCu->tieu_de,
        'do_moi' => $baiDangCu->do_moi,
        'id_loai' => $baiDangCu->id_loai,
        'id_nganh' => $baiDangCu->id_nganh,
        'lop_chuyen_nganh' => $baiDangCu->lop_chuyen_nganh,
        'nam_xuat_ban' => $baiDangCu->nam_xuat_ban,
        'ngay_dang' => now(),
        'trang_thai' => 'san_sang',
    ]);

    // ✅ Sao chép ảnh
    foreach ($baiDangCu->anhBaiDang as $anhCu) {
        AnhBaiDang::create([
            'id_bai_dang' => $baiDangMoi->id_bai_dang,
            'duong_dan' => $anhCu->duong_dan,
            'thu_tu' => $anhCu->thu_tu,
        ]);
    }

    // ✅ Xoá ảnh vật lý của bài cũ
    foreach ($baiDangCu->anhBaiDang as $anh) {
        $relativePath = str_replace('/storage/', '', $anh->duong_dan);
        Storage::disk('public')->delete($relativePath);
    }

    // ✅ Xoá bản ghi ảnh cũ
    AnhBaiDang::where('id_bai_dang', $baiDangCu->id_bai_dang)->delete();

    // ✅ Xoá bài đăng cũ
    $baiDangCu->delete();

    return response()->json([
        'message' => 'Bài đăng đã được đăng lại và bài cũ đã bị xoá.',
        'id_bai_dang' => $baiDangMoi->id_bai_dang
    ]);
}


public function thongKeTheoTrangThai()
{
    $sanSang = BaiDang::where('trang_thai', 'san_sang')->count();
    $daChoTang = BaiDang::where('trang_thai', 'da_cho_tang')->count();
    $viPham = BaiDang::where('trang_thai', 'vi_pham')->count();

    return response()->json([
        'series' => [
            ['label' => 'Sẵn sàng', 'value' => $sanSang],
            ['label' => 'Đã cho tặng', 'value' => $daChoTang],
            ['label' => 'Vi phạm', 'value' => $viPham]
        ],
        'total' => $sanSang + $daChoTang + $viPham
    ]);
}

}
