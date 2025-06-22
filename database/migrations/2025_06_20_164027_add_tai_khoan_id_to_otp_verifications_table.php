<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) { // Hoặc tên bảng chính xác của bạn
            // Thêm cột tai_khoan_id
            $table->integer('tai_khoan_id')->after('id')->nullable(); // Ví dụ: Kiểu integer, sau cột 'id', cho phép null tạm thời
            // Nếu id_tai_khoan của TaiKhoan là unsignedBigInteger, thì dùng dòng dưới
            // $table->unsignedBigInteger('tai_khoan_id')->after('id')->nullable(); 

            // (Tùy chọn) Thêm khóa ngoại nếu bạn muốn ràng buộc toàn vẹn dữ liệu
            // Nếu bạn thêm, hãy đảm bảo cột TaiKhoan.id_tai_khoan cũng là unsignedBigInteger
            // $table->foreign('tai_khoan_id')->references('id_tai_khoan')->on('TaiKhoan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) { // Hoặc tên bảng chính xác của bạn
            // (Tùy chọn) Xóa khóa ngoại nếu đã thêm
            // $table->dropForeign(['tai_khoan_id']);
            $table->dropColumn('tai_khoan_id');
        });
    }
};