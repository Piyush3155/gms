<?php
/**
 * Online Payment Gateway Interface
 * Allows members to make payments using Razorpay, Stripe, or PayPal
 */

require_once '../includes/config.php';
require_once '../includes/payment_gateway.php';

require_permission('payments', 'add');

$message = '';
$error = '';
$paymentOrder = null;

// Get member details if member_id is provided
$memberId = $_GET['member_id'] ?? null;
$member = null;

if ($memberId) {
    $stmt = $conn->prepare("SELECT id, name, email, phone, membership_end FROM members WHERE id = ?");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
}

// Handle payment order creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_order'])) {
    $memberId = intval($_POST['member_id']);
    $amount = floatval($_POST['amount']);
    $gateway = $_POST['gateway'] ?? 'razorpay';
    $purpose = $_POST['purpose'] ?? 'membership';
    
    $paymentGateway = new PaymentGateway($gateway);
    
    $metadata = [
        'purpose' => $purpose,
        'plan_id' => $_POST['plan_id'] ?? null,
        'duration' => $_POST['duration'] ?? null
    ];
    
    $result = $paymentGateway->createOrder($memberId, $amount, $purpose, $metadata);
    
    if ($result['success']) {
        $paymentOrder = $result;
        $message = "Payment order created successfully. Please complete the payment.";
        log_activity('payment_order_created', "Payment order created for member ID: {$memberId}, Amount: {$amount}", 'payments');
    } else {
        $error = $result['error'] ?? 'Failed to create payment order';
    }
}

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_payment'])) {
    $gateway = $_POST['gateway'];
    $transactionId = $_POST['transaction_id'];
    $paymentData = json_decode($_POST['payment_data'], true);
    
    $paymentGateway = new PaymentGateway($gateway);
    $result = $paymentGateway->verifyPayment($transactionId, $paymentData);
    
    if ($result['success'] && $result['verified']) {
        $message = "Payment verified successfully! Receipt Number: " . $result['receipt_number'];
        log_activity('payment_verified', "Payment verified: Transaction ID {$transactionId}", 'payments');
        
        // Redirect to success page
        header("Location: payments.php?success=1&receipt=" . $result['receipt_number']);
        exit;
    } else {
        $error = $result['error'] ?? 'Payment verification failed';
    }
}

// Get all members for dropdown
$members = $conn->query("SELECT id, name, email, phone FROM members ORDER BY name ASC");

// Get membership plans
$plans = $conn->query("SELECT id, name, price, duration FROM plans ORDER BY price ASC");

// Get recent online transactions
$recentTransactions = $conn->query("
    SELECT pt.*, m.name as member_name
    FROM payment_transactions pt
    JOIN members m ON pt.member_id = m.id
    ORDER BY pt.created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payments - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css?v=1.0">
    
    <!-- Razorpay SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    
    <!-- Stripe SDK -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=YOUR_PAYPAL_CLIENT_ID&currency=USD"></script>
    
    <style>
        .payment-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .payment-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .payment-card.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .gateway-logo {
            height: 40px;
            object-fit: contain;
        }
        
        .transaction-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-failed {
            background: #fee2e2;
            color: #7f1d1d;
        }
        
        .badge-refunded {
            background: #e0e7ff;
            color: #3730a3;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Online Payment Gateway</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$paymentOrder): ?>
                            <!-- Payment Order Creation Form -->
                            <form method="POST" id="paymentForm">
                                <input type="hidden" name="create_order" value="1">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="member_id" class="form-label">Select Member *</label>
                                        <select class="form-select" id="member_id" name="member_id" required>
                                            <option value="">Choose member...</option>
                                            <?php while ($m = $members->fetch_assoc()): ?>
                                                <option value="<?php echo $m['id']; ?>" <?php echo ($member && $member['id'] == $m['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($m['name']); ?> - <?php echo htmlspecialchars($m['email']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="purpose" class="form-label">Payment Purpose *</label>
                                        <select class="form-select" id="purpose" name="purpose" required>
                                            <option value="membership">Membership Renewal</option>
                                            <option value="personal_training">Personal Training</option>
                                            <option value="supplement">Supplements</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="plan_id" class="form-label">Membership Plan (if applicable)</label>
                                        <select class="form-select" id="plan_id" name="plan_id">
                                            <option value="">Select plan...</option>
                                            <?php
                                            $plans->data_seek(0);
                                            while ($plan = $plans->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $plan['id']; ?>" data-price="<?php echo $plan['price']; ?>" data-duration="<?php echo $plan['duration']; ?>">
                                                    <?php echo htmlspecialchars($plan['name']); ?> - ₹<?php echo number_format($plan['price'], 2); ?> (<?php echo $plan['duration']; ?> months)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount (₹) *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="1" required>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="duration" name="duration">
                                
                                <h5 class="mt-4 mb-3">Select Payment Gateway</h5>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="payment-card" data-gateway="razorpay">
                                            <input type="radio" name="gateway" value="razorpay" id="razorpay" class="d-none" checked>
                                            <label for="razorpay" class="d-flex align-items-center justify-content-between w-100">
                                                <div>
                                                    <h5 class="mb-1">Razorpay</h5>
                                                    <small class="text-muted">UPI, Cards, Net Banking</small>
                                                </div>
                                                <i class="fas fa-credit-card fa-2x text-primary"></i>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="payment-card" data-gateway="stripe">
                                            <input type="radio" name="gateway" value="stripe" id="stripe" class="d-none">
                                            <label for="stripe" class="d-flex align-items-center justify-content-between w-100">
                                                <div>
                                                    <h5 class="mb-1">Stripe</h5>
                                                    <small class="text-muted">International Cards</small>
                                                </div>
                                                <i class="fab fa-stripe fa-2x text-primary"></i>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="payment-card" data-gateway="paypal">
                                            <input type="radio" name="gateway" value="paypal" id="paypal" class="d-none">
                                            <label for="paypal" class="d-flex align-items-center justify-content-between w-100">
                                                <div>
                                                    <h5 class="mb-1">PayPal</h5>
                                                    <small class="text-muted">PayPal Account</small>
                                                </div>
                                                <i class="fab fa-paypal fa-2x text-primary"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-lock me-2"></i>Proceed to Payment
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Payment Processing -->
                            <div id="paymentProcessing">
                                <h4 class="mb-3">Complete Your Payment</h4>
                                
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <h5>Payment Details</h5>
                                        <p class="mb-1"><strong>Member:</strong> <?php echo htmlspecialchars($paymentOrder['member']['name']); ?></p>
                                        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($paymentOrder['member']['email']); ?></p>
                                        <p class="mb-1"><strong>Amount:</strong> <?php echo $paymentOrder['config']['currency']; ?> <?php echo number_format($paymentOrder['order']['amount'] / 100, 2); ?></p>
                                        <p class="mb-0"><strong>Order ID:</strong> <?php echo htmlspecialchars($paymentOrder['order']['id']); ?></p>
                                    </div>
                                </div>
                                
                                <button type="button" id="payButton" class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Pay Now
                                </button>
                                
                                <a href="online_payments.php" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                            
                            <script>
                                const paymentOrder = <?php echo json_encode($paymentOrder); ?>;
                                const gateway = '<?php echo $paymentOrder['gateway']; ?>';
                                
                                document.getElementById('payButton').addEventListener('click', function() {
                                    if (gateway === 'razorpay') {
                                        initRazorpay();
                                    } else if (gateway === 'stripe') {
                                        initStripe();
                                    } else if (gateway === 'paypal') {
                                        initPayPal();
                                    }
                                });
                                
                                function initRazorpay() {
                                    const options = {
                                        key: paymentOrder.config.key,
                                        amount: paymentOrder.order.amount,
                                        currency: paymentOrder.config.currency,
                                        name: '<?php echo SITE_NAME; ?>',
                                        description: 'Membership Payment',
                                        order_id: paymentOrder.order.id,
                                        handler: function(response) {
                                            verifyPayment('razorpay', response.razorpay_payment_id, {
                                                razorpay_order_id: response.razorpay_order_id,
                                                razorpay_payment_id: response.razorpay_payment_id,
                                                razorpay_signature: response.razorpay_signature
                                            });
                                        },
                                        prefill: {
                                            name: paymentOrder.member.name,
                                            email: paymentOrder.member.email,
                                            contact: paymentOrder.member.phone
                                        },
                                        theme: {
                                            color: '#3b82f6'
                                        }
                                    };
                                    
                                    const rzp = new Razorpay(options);
                                    rzp.open();
                                }
                                
                                function initStripe() {
                                    const stripe = Stripe(paymentOrder.config.key);
                                    
                                    stripe.confirmCardPayment(paymentOrder.order.client_secret, {
                                        payment_method: {
                                            card: {
                                                // Card details would be collected here
                                            },
                                            billing_details: {
                                                name: paymentOrder.member.name,
                                                email: paymentOrder.member.email
                                            }
                                        }
                                    }).then(function(result) {
                                        if (result.error) {
                                            alert('Payment failed: ' + result.error.message);
                                        } else {
                                            verifyPayment('stripe', result.paymentIntent.id, result.paymentIntent);
                                        }
                                    });
                                }
                                
                                function initPayPal() {
                                    // PayPal implementation would go here
                                    alert('PayPal integration coming soon');
                                }
                                
                                function verifyPayment(gateway, transactionId, paymentData) {
                                    const form = document.createElement('form');
                                    form.method = 'POST';
                                    form.action = 'online_payments.php';
                                    
                                    const fields = {
                                        verify_payment: '1',
                                        gateway: gateway,
                                        transaction_id: transactionId,
                                        payment_data: JSON.stringify(paymentData)
                                    };
                                    
                                    for (const [key, value] of Object.entries(fields)) {
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = key;
                                        input.value = value;
                                        form.appendChild(input);
                                    }
                                    
                                    document.body.appendChild(form);
                                    form.submit();
                                }
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Online Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Member</th>
                                        <th>Amount</th>
                                        <th>Gateway</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentTransactions->num_rows > 0): ?>
                                        <?php while ($txn = $recentTransactions->fetch_assoc()): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars(substr($txn['transaction_id'], 0, 20)); ?>...</code></td>
                                                <td><?php echo htmlspecialchars($txn['member_name']); ?></td>
                                                <td><?php echo $txn['currency']; ?> <?php echo number_format($txn['amount'], 2); ?></td>
                                                <td><span class="badge bg-info"><?php echo ucfirst($txn['gateway']); ?></span></td>
                                                <td>
                                                    <span class="transaction-badge badge-<?php echo $txn['status']; ?>">
                                                        <?php echo ucfirst($txn['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $txn['receipt_number'] ?? '-'; ?></td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($txn['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($txn['status'] === 'success'): ?>
                                                        <a href="generate_receipt.php?txn_id=<?php echo $txn['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> Receipt
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No transactions found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment gateway selection
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });
        
        // Auto-fill amount when plan is selected
        document.getElementById('plan_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;
            const duration = selectedOption.dataset.duration;
            
            if (price) {
                document.getElementById('amount').value = price;
                document.getElementById('duration').value = duration;
            }
        });
        
        // Initialize selected gateway
        const selectedGateway = document.querySelector('input[name="gateway"]:checked');
        if (selectedGateway) {
            selectedGateway.closest('.payment-card').classList.add('selected');
        }
    </script>
</body>
</html>
