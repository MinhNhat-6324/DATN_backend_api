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
 * GET /api/bai-dang/nganh/{id_nganh}/loai/{id_loai}
 * Lấy danh sách bài đăng theo ngành và loại sản phẩm
 */
public function getByNganhVaLoai($id_nganh, $id_loai)
{
    $baiDangQuery = BaiDang::with([
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
    ->where('id_nganh', $id_nganh);

    // Nếu id_loai khác -1 thì lọc theo loại
    if ($id_loai != -1) {
        $baiDangQuery->where('id_loai', $id_loai);
    }

    $result = $baiDangQuery->orderByDesc('ngay_dang')->get();

    return response()->json($result);
}

/**
 * GET /api/bai-dang/tieu-de/{tieu_de}
 * Tìm kiếm bài đăng theo tiêu đề (gần đúng)
 */
public function getByTieuDe($tieu_de)
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
        ->where('tieu_de', 'LIKE', '%' . $tieu_de . '%')
        ->orderByDesc('ngay_dang')
        ->get();

    return response()->json($baiDangs);
}

/**
 * GET /api/bai-dang/loc/{id_nganh}/{id_loai}/{tieu_de}
 * Lọc bài đăng theo ngành, loại và tiêu đề gần đúng
 */
public function locBaiDang($id_nganh, $id_loai, $tieu_de)
{
    $query = BaiDang::with([
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
        ->where('id_nganh', $id_nganh);

    if ($id_loai != -1) {
        $query->where('id_loai', $id_loai);
    }

    if ($tieu_de != '-' && $tieu_de != '') {
        $query->where('tieu_de', 'LIKE', '%' . $tieu_de . '%');
    }

    $result = $query->orderByDesc('ngay_dang')->get();

    return response()->json($result);
}

/**
 * GET /api/bai-dang/loai/{id_loai}/tieu-de/{tieu_de}
 * Lọc bài đăng theo loại sản phẩm và tiêu đề gần đúng
 */
/**
 * GET /api/bai-dang/loai/{id_loai}/tieu-de/{tieu_de}
 * Lọc bài đăng theo loại sản phẩm và tiêu đề gần đúng
 */
/**
 * GET /api/bai-dang/loai/{id_loai}/tieu-de/{tieu_de}
 * Lọc bài đăng theo loại sản phẩm và tiêu đề gần đúng
 */
public function locTheoLoaiVaTieuDe($id_loai, $tieu_de = null)
{
    $query = BaiDang::with([
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
        ->where('id_loai', $id_loai); // luôn lọc theo loại

    // ❌ KHÔNG lọc theo tiêu đề nếu rỗng, null hoặc là '-'
    if ($tieu_de !== null && $tieu_de !== '' && $tieu_de !== '-') {
        $query->where('tieu_de', 'LIKE', '%' . $tieu_de . '%');
    }

    return response()->json($query->orderByDesc('ngay_dang')->get());
}
/**
 * GET /api/bai-dang/loai/{id_loai}
 * Lấy danh sách bài đăng theo loại sản phẩm
 */
public function getByLoai($id_loai)
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
        ->where('id_loai', $id_loai)
        ->orderByDesc('ngay_dang')
        ->get();

    return response()->json($baiDangs);
}
/**
     * POST /api/bai-dang
     * Tạo một bài đăng mới và lưu ảnh kèm theo
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_tai_khoan' => 'required|integer|exists:TaiKhoan,id_tai_khoan',
            'tieu_de' => 'required|string|max:255',
            'gia' => 'required|numeric',
            'do_moi' => 'required|numeric|min:0|max:100',
            'id_loai' => 'required|integer|exists:LoaiSanPham,id_loai',
            'id_nganh' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
            'hinh_anh' => 'nullable|array',
            'hinh_anh.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Tạo bài đăng
        $baiDang = BaiDang::create([
            'id_tai_khoan' => $request->id_tai_khoan,
            'tieu_de' => $request->tieu_de,
            'gia' => $request->gia,
            'do_moi' => $request->do_moi,
            'id_loai' => $request->id_loai,
            'id_nganh' => $request->id_nganh,
            'ngay_dang' => Carbon::now(),
            'trang_thai' => 'dang', // hoặc giá trị mặc định
        ]);

        // Nếu có ảnh được gửi kèm, lưu chúng
        if ($request->hasFile('hinh_anh')) {
            foreach ($request->file('hinh_anh') as $index => $image) {
                $path = $image->store('public/hinh_anh_bai_dang');
                $url = Storage::url($path); // ví dụ: /storage/hinh_anh_bai_dang/abc.jpg

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


}
