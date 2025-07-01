<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BaiDang;
use App\Models\AnhBaiDang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BaiDangController extends Controller
{
    /**
     * GET /api/bai-dang
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
            ->where('trang_thai', 'san_sang') // ✅
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    }

    /**
     * GET /api/bai-dang/nganh/{id}
     */
    public function getByNganh($id_nganh)
    {
        $baiDang = BaiDang::with([
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
        ->where('id_nganh', $id_nganh)
        ->where('trang_thai', 'san_sang') // ✅
        ->orderByDesc('ngay_dang')
        ->get();

        return response()->json($baiDang);
    }

    /**
     * GET /api/bai-dang/{id}
     */
    public function show($id)
    {
        $baiDang = BaiDang::with(['anhBaiDang:id_bai_dang,duong_dan,thu_tu'])
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
     */
    public function getByNganhVaLoai($id_nganh, $id_loai)
    {
        $baiDangQuery = BaiDang::with([
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
        ->where('id_nganh', $id_nganh)
        ->where('trang_thai', 'san_sang'); // ✅

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
            ->where('trang_thai', 'san_sang') // ✅
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    }

    /**
     * GET /api/bai-dang/loc/{id_nganh}/{id_loai}/{tieu_de}
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
            ->where('id_nganh', $id_nganh)
            ->where('trang_thai', 'san_sang'); // ✅

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
            ->where('id_loai', $id_loai)
            ->where('trang_thai', 'san_sang'); // ✅

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
            ->where('trang_thai', 'san_sang') // ✅
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
            'gia' => 'required|numeric',
            'do_moi' => 'required|numeric|min:0|max:100',
            'id_loai' => 'required|integer|exists:LoaiSanPham,id_loai',
            'id_nganh' => 'required|integer|exists:ChuyenNganhSanPham,id_nganh',
            'hinh_anh' => 'nullable|array',
            'hinh_anh.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $baiDang = BaiDang::create([
            'id_tai_khoan' => $request->id_tai_khoan,
            'tieu_de' => $request->tieu_de,
            'gia' => $request->gia,
            'do_moi' => $request->do_moi,
            'id_loai' => $request->id_loai,
            'id_nganh' => $request->id_nganh,
            'ngay_dang' => Carbon::now(),
            'trang_thai' => 'san_sang',
        ]);

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
}
