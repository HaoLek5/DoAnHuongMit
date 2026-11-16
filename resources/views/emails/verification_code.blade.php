<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực Hương Mít</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 24px 12px;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 6px 0 0;
            font-size: 13px;
            opacity: 0.92;
        }
        .body {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .desc {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .code-box {
            background: #fff1f2;
            border: 2px dashed #fda4af;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .code-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #e11d48;
            margin-bottom: 8px;
        }
        .code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #be123c;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            padding-left: 8px;
        }
        .expiry {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
        }
        .note {
            background: #f8fafc;
            border-left: 3px solid #e11d48;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
            margin-top: 24px;
        }
        .footer {
            border-top: 1px solid #f1f5f9;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            background: #fafafa;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Hương Mít</h1>
            <p>Mẹ & Bé — Chăm sóc gia đình thân yêu</p>
        </div>
        <div class="body">
            <div class="greeting">
                Xin chào {{ $recipientName ?? 'Quý khách' }},
            </div>
            <div class="desc">
                @if($action === 'change_password')
                    Bạn vừa yêu cầu <strong>đổi mật khẩu</strong> cho tài khoản tại Hương Mít. Vui lòng sử dụng mã xác thực bên dưới để xác nhận thao tác:
                @elseif($action === 'reset_password' || $action === 'forgot_password')
                    Bạn vừa yêu cầu <strong>khôi phục / đặt lại mật khẩu</strong> cho tài khoản tại Hương Mít. Vui lòng sử dụng mã xác thực bên dưới để hoàn tất việc đổi mật khẩu mới:
                @else
                    Cảm ơn bạn đã đăng ký tài khoản tại <strong>Hương Mít</strong>. Vui lòng sử dụng mã xác thực bên dưới để kích hoạt tài khoản của bạn:
                @endif
            </div>

            <div class="code-box">
                <div class="code-label">MÃ XÁC THỰC CỦA BẠN (OTP)</div>
                <div class="code">{{ $code }}</div>
                <div class="expiry">Mã có hiệu lực trong vòng <strong>15 phút</strong></div>
            </div>

            <div class="note">
                <strong>Lưu ý bảo mật:</strong> Không chia sẻ mã xác thực này cho bất kỳ ai, kể cả nhân viên hỗ trợ. Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email hoặc liên hệ với chúng tôi để kiểm tra an toàn tài khoản.
            </div>
        </div>
        <div class="footer">
            <p><strong>Cửa hàng Mẹ & Bé Hương Mít</strong></p>
            <p>Email này được gửi tự động từ hệ thống. Vui lòng không trả lời trực tiếp email này.</p>
        </div>
    </div>
</body>
</html>

