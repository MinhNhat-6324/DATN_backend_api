<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tin nhắn mới</title>
</head>
<body>
    <h3>📬 Bạn có một tin nhắn mới!</h3>
    <p><strong>{{ $tenNguoiGui }}</strong> ({{ $emailNguoiGui }}) đã gửi bạn tin nhắn:</p>
    <blockquote style="color: #333; background: #f5f5f5; padding: 10px; border-left: 5px solid #0079CF;">
        {{ $noiDung }}
    </blockquote>
    <p>Vui lòng kiểm tra và phản hồi nếu cần.</p>
    <hr>
    <p><small>Email này được gửi tự động từ hệ thống Trao Đổi Sách.</small></p>
</body>
</html>
