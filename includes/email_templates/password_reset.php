<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container { 
            max-width: 600px; 
            margin: 20px auto; 
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .email-header { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        .email-header h1 { 
            margin: 0; 
            font-size: 28px;
            font-weight: 700;
        }
        .email-content { 
            padding: 40px 30px; 
        }
        .alert-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white !important;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 25px;
            margin: 20px 0;
            font-weight: 600;
            font-size: 16px;
        }
        .email-footer { 
            background: #2c3e50; 
            color: white; 
            padding: 30px; 
            text-align: center; 
        }
        .email-footer p {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔒 Password Reset Request</h1>
        </div>
        
        <div class="email-content">
            <h2>Hello <?php echo htmlspecialchars($data['user_name']); ?>!</h2>
            
            <p>We received a request to reset your password for your <?php echo htmlspecialchars($data['gym_name']); ?> account.</p>
            
            <div class="alert-box">
                <p><strong>⚠️ Security Notice</strong></p>
                <p>If you didn't request this password reset, please ignore this email or contact support if you have concerns.</p>
            </div>
            
            <p>To reset your password, click the button below. This link will expire in <strong><?php echo $data['expiry_time']; ?></strong>.</p>
            
            <center>
                <a href="<?php echo $data['reset_link']; ?>" class="cta-button">Reset Password</a>
            </center>
            
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #667eea;"><?php echo $data['reset_link']; ?></p>
            
            <p><strong>Security Tips:</strong></p>
            <ul>
                <li>Choose a strong, unique password</li>
                <li>Don't share your password with anyone</li>
                <li>Use a combination of letters, numbers, and special characters</li>
                <li>Avoid using common words or personal information</li>
            </ul>
        </div>
        
        <div class="email-footer">
            <p><strong><?php echo htmlspecialchars($data['gym_name']); ?></strong></p>
            <?php if (!empty($data['contact'])): ?>
                <p>📞 <?php echo htmlspecialchars($data['contact']); ?></p>
            <?php endif; ?>
            <?php if (!empty($data['email'])): ?>
                <p>📧 <?php echo htmlspecialchars($data['email']); ?></p>
            <?php endif; ?>
            <p style="margin-top: 20px; opacity: 0.8;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['gym_name']); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
