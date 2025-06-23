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
        $baiDangs = BaiDang::with(['anhBaiDang' => function ($query) {
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
            ->orderByDesc('ngay_dang')
            ->get();

        return response()->json($baiDangs);
    }

    /**
     * GET /api/bai-dang/{id}
     * Chi tiết 1 bài đăng
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
}
