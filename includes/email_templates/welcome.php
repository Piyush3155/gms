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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        .email-header h1 { 
            margin: 0; 
            font-size: 28px;
            font-weight: 700;
        }
        .email-header p { 
            margin: 10px 0 0 0; 
            opacity: 0.9;
        }
        .email-content { 
            padding: 40px 30px; 
        }
        .email-content h2 {
            color: #667eea;
            margin-top: 0;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box strong {
            color: #667eea;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin: 20px 0;
            font-weight: 600;
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
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Welcome to <?php echo htmlspecialchars($data['gym_name']); ?>!</h1>
            <p>Your Fitness Journey Starts Here</p>
        </div>
        
        <div class="email-content">
            <h2>Hello <?php echo htmlspecialchars($data['member_name']); ?>! 👋</h2>
            
            <p>We're thrilled to have you join our fitness family! Your membership has been successfully activated, and you're all set to begin your transformation journey.</p>
            
            <div class="info-box">
                <p><strong>Membership Details:</strong></p>
                <p><strong>Plan:</strong> <?php echo htmlspecialchars($data['membership_plan']); ?></p>
                <p><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($data['join_date'])); ?></p>
                <p><strong>Expiry Date:</strong> <?php echo date('F d, Y', strtotime($data['expiry_date'])); ?></p>
            </div>
            
            <p><strong>What's Next?</strong></p>
            <ul>
                <li>Visit the gym and complete your induction with our trainers</li>
                <li>Access your personalized dashboard to view workout plans</li>
                <li>Book group classes and personal training sessions</li>
                <li>Track your progress and set your fitness goals</li>
            </ul>
            
            <p>If you have any questions or need assistance, our team is always here to help!</p>
            
            <p>Let's make every workout count! 💪</p>
        </div>
        
        <div class="email-footer">
            <p><strong><?php echo htmlspecialchars($data['gym_name']); ?></strong></p>
            <?php if (!empty($data['address'])): ?>
                <p>📍 <?php echo htmlspecialchars($data['address']); ?></p>
            <?php endif; ?>
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
