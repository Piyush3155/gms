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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
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
        .receipt-box {
            background: #f8f9fa;
            border: 2px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .receipt-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-box td {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .receipt-box tr:last-child td {
            border-bottom: none;
        }
        .receipt-box .label {
            color: #6b7280;
            font-weight: 500;
        }
        .receipt-box .value {
            text-align: right;
            font-weight: 600;
        }
        .total-row {
            background: #10b981;
            color: white;
            font-size: 18px;
            padding: 15px !important;
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
            <h1>✓ Payment Received</h1>
            <p>Thank you for your payment!</p>
        </div>
        
        <div class="email-content">
            <h2>Hello <?php echo htmlspecialchars($data['member_name']); ?>!</h2>
            
            <p>We've received your payment. Here's your receipt for your records:</p>
            
            <div class="receipt-box">
                <h3 style="margin-top: 0; color: #10b981;">Payment Receipt</h3>
                <table>
                    <tr>
                        <td class="label">Invoice Number:</td>
                        <td class="value"><?php echo htmlspecialchars($data['invoice_no']); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Payment Date:</td>
                        <td class="value"><?php echo date('F d, Y', strtotime($data['payment_date'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Membership Plan:</td>
                        <td class="value"><?php echo htmlspecialchars($data['plan_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Payment Method:</td>
                        <td class="value"><?php echo ucfirst(str_replace('_', ' ', $data['payment_method'])); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Amount Paid:</strong></td>
                        <td class="value"><strong>₹<?php echo number_format($data['amount'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
            
            <p>This receipt confirms your payment has been successfully processed. You can download and print this email for your records.</p>
            
            <p>Thank you for being a valued member of <?php echo htmlspecialchars($data['gym_name']); ?>!</p>
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
