<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadGoogleImages extends Command
{
    protected $signature = 'images:download-google';
    protected $description = 'Tải các ảnh từ link Google và cập nhật đường dẫn mới';

    public function handle()
    {
        $records = DB::table('anh_bai_dang')
            ->where('duong_dan', 'like', 'https://www.google.com/%')
            ->get();

        foreach ($records as $record) {
            try {
                $url = $record->duong_dan;
                $imageData = @file_get_contents($url);

                if ($imageData === false) {
                    $this->warn("Không tải được ảnh: {$url}");
                    continue;
                }

                $filename = 'img_' . Str::random(10) . '.jpg';
                $path = 'hinh_anh_bai_dang/' . $filename;

                Storage::disk('public')->put($path, $imageData);

                DB::table('anh_bai_dang')
                    ->where('id_anh', $record->id_anh)
                    ->update([
                        'duong_dan' => '/storage/' . $path
                    ]);

                $this->info("Đã tải và cập nhật ảnh cho id_anh {$record->id_anh}");
            } catch (\Exception $e) {
                $this->error("Lỗi khi xử lý id_anh {$record->id_anh}: " . $e->getMessage());
            }
        }

        $this->info('Hoàn tất cập nhật ảnh từ Google.');
    }
}
