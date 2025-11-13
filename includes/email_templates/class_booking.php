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
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); 
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
        .class-info-box {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            border-left: 4px solid #06b6d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .class-info-box p {
            margin: 10px 0;
        }
        .class-info-box strong {
            color: #0891b2;
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
            <h1>🎉 Class Booking Confirmed!</h1>
        </div>
        
        <div class="email-content">
            <h2>Hello <?php echo htmlspecialchars($data['member_name']); ?>!</h2>
            
            <p>Great news! Your spot has been reserved for the upcoming class.</p>
            
            <div class="class-info-box">
                <h3 style="margin-top: 0; color: #0891b2;">Class Details</h3>
                <p><strong>Class Name:</strong> <?php echo htmlspecialchars($data['class_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($data['class_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($data['start_time'])); ?> - <?php echo date('g:i A', strtotime($data['end_time'])); ?></p>
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($data['trainer_name']); ?></p>
            </div>
            
            <p><strong>Important Reminders:</strong></p>
            <ul>
                <li>Please arrive 10 minutes early</li>
                <li>Bring your water bottle and towel</li>
                <li>Wear appropriate workout attire</li>
                <li>If you need to cancel, please do so at least 2 hours in advance</li>
            </ul>
            
            <p>We're excited to see you in class! Get ready for an amazing workout session! 💪</p>
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
