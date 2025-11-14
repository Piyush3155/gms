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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
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
        .email-content h2 {
            color: #f59e0b;
            margin-top: 0;
        }
        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .alert-box strong {
            color: #d97706;
            font-size: 18px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        .email-footer a {
            color: #f59e0b;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>⚠️ Membership Expiry Reminder</h1>
        </div>
        
        <div class="email-content">
            <h2>Hello <?php echo htmlspecialchars($data['member_name']); ?>!</h2>
            
            <p>This is a friendly reminder that your membership at <strong><?php echo htmlspecialchars($data['gym_name']); ?></strong> is expiring soon.</p>
            
            <div class="alert-box">
                <p><strong>⏰ <?php echo $data['days_remaining']; ?> Days Remaining</strong></p>
                <p>Your membership will expire on <strong><?php echo !empty($data['expiry_date']) ? date('F d, Y', strtotime($data['expiry_date'])) : 'N/A'; ?></strong></p>
            </div>
            
            <p>Don't let your fitness journey hit pause! Renew your membership today and continue crushing your goals.</p>
            
            <p><strong>Benefits of Renewing Now:</strong></p>
            <ul>
                <li>✓ Uninterrupted access to all facilities</li>
                <li>✓ Continue with your current training schedule</li>
                <li>✓ Maintain your progress and momentum</li>
                <li>✓ Special renewal discounts may apply</li>
            </ul>
            
            <center>
                <a href="<?php echo $data['renewal_link']; ?>" class="cta-button">Renew Membership Now</a>
            </center>
            
            <p>If you've already renewed, please disregard this message. For any questions or assistance with renewal, feel free to contact us.</p>
            
            <p>Keep pushing forward! 💪</p>
        </div>
        
        <div class="email-footer">
            <p><strong><?php echo htmlspecialchars($data['gym_name']); ?></strong></p>
            <?php if (!empty($data['contact'])): ?>
                <p>📞 <?php echo htmlspecialchars($data['contact']); ?></p>
            <?php endif; ?>
            <?php if (!empty($data['email'])): ?>
                <p>📧 <a href="mailto:<?php echo htmlspecialchars($data['email']); ?>"><?php echo htmlspecialchars($data['email']); ?></a></p>
            <?php endif; ?>
            <p style="margin-top: 20px; opacity: 0.8;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['gym_name']); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
