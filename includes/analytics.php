<?php
/**
 * Advanced Analytics and Reporting System
 * Provides comprehensive analytics for the Gym Management System
 */

class Analytics {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics($start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-m-01'); // First day of current month
        if (!$end_date) $end_date = date('Y-m-t'); // Last day of current month
        
        // Total revenue
        $stmt = $this->conn->prepare("SELECT 
            SUM(amount) as total_revenue,
            COUNT(*) as total_transactions,
            AVG(amount) as average_transaction
            FROM payments 
            WHERE payment_date BETWEEN ? AND ? AND status = 'paid'");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $revenue = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Revenue by payment method
        $stmt = $this->conn->prepare("SELECT 
            method,
            SUM(amount) as amount,
            COUNT(*) as count
            FROM payments 
            WHERE payment_date BETWEEN ? AND ? AND status = 'paid'
            GROUP BY method");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $by_method = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Revenue by plan
        $stmt = $this->conn->prepare("SELECT 
            pl.name as plan_name,
            SUM(p.amount) as amount,
            COUNT(*) as count
            FROM payments p
            JOIN plans pl ON p.plan_id = pl.id
            WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'paid'
            GROUP BY pl.id, pl.name");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $by_plan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Daily revenue trend
        $stmt = $this->conn->prepare("SELECT 
            DATE(payment_date) as date,
            SUM(amount) as amount,
            COUNT(*) as count
            FROM payments 
            WHERE payment_date BETWEEN ? AND ? AND status = 'paid'
            GROUP BY DATE(payment_date)
            ORDER BY date");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $daily_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'summary' => $revenue,
            'by_method' => $by_method,
            'by_plan' => $by_plan,
            'daily_trend' => $daily_trend
        ];
    }
    
    /**
     * Get attendance analytics
     */
    public function getAttendanceAnalytics($start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-m-01');
        if (!$end_date) $end_date = date('Y-m-t');
        
        // Overall statistics
        $stmt = $this->conn->prepare("SELECT 
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(*) as total_checkins,
            COUNT(DISTINCT DATE(date)) as active_days
            FROM attendance 
            WHERE date BETWEEN ? AND ? AND status = 'present'");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Attendance by role
        $stmt = $this->conn->prepare("SELECT 
            role,
            COUNT(*) as count,
            COUNT(DISTINCT user_id) as unique_users
            FROM attendance 
            WHERE date BETWEEN ? AND ? AND status = 'present'
            GROUP BY role");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $by_role = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Daily attendance trend
        $stmt = $this->conn->prepare("SELECT 
            DATE(date) as date,
            COUNT(*) as count,
            COUNT(DISTINCT user_id) as unique_users
            FROM attendance 
            WHERE date BETWEEN ? AND ? AND status = 'present'
            GROUP BY DATE(date)
            ORDER BY date");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $daily_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Peak hours
        $stmt = $this->conn->prepare("SELECT 
            HOUR(check_in) as hour,
            COUNT(*) as count
            FROM attendance 
            WHERE date BETWEEN ? AND ? AND check_in IS NOT NULL
            GROUP BY HOUR(check_in)
            ORDER BY count DESC
            LIMIT 5");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $peak_hours = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Top members by attendance
        $stmt = $this->conn->prepare("SELECT 
            u.name,
            u.email,
            COUNT(*) as visit_count
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE a.date BETWEEN ? AND ? AND a.status = 'present' AND a.role = 'member'
            GROUP BY a.user_id, u.name, u.email
            ORDER BY visit_count DESC
            LIMIT 10");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $top_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'summary' => $summary,
            'by_role' => $by_role,
            'daily_trend' => $daily_trend,
            'peak_hours' => $peak_hours,
            'top_members' => $top_members
        ];
    }
    
    /**
     * Get member growth analytics
     */
    public function getMemberGrowthAnalytics($months = 12) {
        // Member growth over time
        $start_date = date('Y-m-01', strtotime("-$months months"));
        
        $stmt = $this->conn->prepare("SELECT 
            DATE_FORMAT(join_date, '%Y-%m') as month,
            COUNT(*) as new_members,
            SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(join_date, '%Y-%m')) as total_members
            FROM members 
            WHERE join_date >= ?
            GROUP BY DATE_FORMAT(join_date, '%Y-%m')
            ORDER BY month");
        $stmt->bind_param("s", $start_date);
        $stmt->execute();
        $growth_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Active vs expired members
        $result = $this->conn->query("SELECT 
            status,
            COUNT(*) as count
            FROM members
            GROUP BY status");
        $by_status = $result->fetch_all(MYSQLI_ASSOC);
        
        // Members by plan
        $result = $this->conn->query("SELECT 
            pl.name as plan_name,
            COUNT(m.id) as count
            FROM members m
            JOIN plans pl ON m.plan_id = pl.id
            WHERE m.status = 'active'
            GROUP BY pl.id, pl.name
            ORDER BY count DESC");
        $by_plan = $result->fetch_all(MYSQLI_ASSOC);
        
        // Member retention rate
        $three_months_ago = date('Y-m-d', strtotime('-3 months'));
        $stmt = $this->conn->prepare("SELECT 
            COUNT(*) as old_members,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as still_active
            FROM members 
            WHERE join_date <= ?");
        $stmt->bind_param("s", $three_months_ago);
        $stmt->execute();
        $retention_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $retention_rate = $retention_data['old_members'] > 0 
            ? ($retention_data['still_active'] / $retention_data['old_members']) * 100 
            : 0;
        
        return [
            'growth_trend' => $growth_trend,
            'by_status' => $by_status,
            'by_plan' => $by_plan,
            'retention_rate' => round($retention_rate, 2)
        ];
    }
    
    /**
     * Get trainer performance metrics
     */
    public function getTrainerPerformance() {
        // Members assigned to each trainer
        $result = $this->conn->query("SELECT 
            t.name as trainer_name,
            t.specialization,
            COUNT(m.id) as total_members,
            SUM(CASE WHEN m.status = 'active' THEN 1 ELSE 0 END) as active_members
            FROM trainers t
            LEFT JOIN members m ON t.id = m.trainer_id
            GROUP BY t.id, t.name, t.specialization
            ORDER BY active_members DESC");
        $trainer_members = $result->fetch_all(MYSQLI_ASSOC);
        
        // Trainer attendance
        $current_month = date('Y-m');
        $stmt = $this->conn->prepare("SELECT 
            u.name as trainer_name,
            COUNT(*) as attendance_count,
            COUNT(DISTINCT DATE(a.date)) as days_present
            FROM attendance a
            JOIN users u ON a.user_id = u.id
            WHERE a.role = 'trainer' 
            AND DATE_FORMAT(a.date, '%Y-%m') = ?
            AND a.status = 'present'
            GROUP BY a.user_id, u.name
            ORDER BY attendance_count DESC");
        $stmt->bind_param("s", $current_month);
        $stmt->execute();
        $trainer_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Classes conducted
        $stmt = $this->conn->prepare("SELECT 
            t.name as trainer_name,
            COUNT(gc.id) as classes_conducted,
            AVG(gc.capacity) as avg_class_size
            FROM group_classes gc
            JOIN trainers t ON gc.trainer_id = t.id
            WHERE DATE_FORMAT(gc.class_date, '%Y-%m') = ?
            AND gc.status = 'completed'
            GROUP BY t.id, t.name
            ORDER BY classes_conducted DESC");
        $stmt->bind_param("s", $current_month);
        $stmt->execute();
        $classes_conducted = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'member_assignment' => $trainer_members,
            'attendance' => $trainer_attendance,
            'classes' => $classes_conducted
        ];
    }
    
    /**
     * Get expense analytics
     */
    public function getExpenseAnalytics($start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-m-01');
        if (!$end_date) $end_date = date('Y-m-t');
        
        // Total expenses
        $stmt = $this->conn->prepare("SELECT 
            SUM(amount) as total_expenses,
            COUNT(*) as total_transactions,
            AVG(amount) as average_expense
            FROM expenses 
            WHERE expense_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Expenses by category
        $stmt = $this->conn->prepare("SELECT 
            category,
            SUM(amount) as amount,
            COUNT(*) as count
            FROM expenses 
            WHERE expense_date BETWEEN ? AND ?
            GROUP BY category
            ORDER BY amount DESC");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $by_category = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Daily expense trend
        $stmt = $this->conn->prepare("SELECT 
            DATE(expense_date) as date,
            SUM(amount) as amount
            FROM expenses 
            WHERE expense_date BETWEEN ? AND ?
            GROUP BY DATE(expense_date)
            ORDER BY date");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $daily_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return [
            'summary' => $summary,
            'by_category' => $by_category,
            'daily_trend' => $daily_trend
        ];
    }
    
    /**
     * Get profit & loss statement
     */
    public function getProfitLoss($start_date = null, $end_date = null) {
        if (!$start_date) $start_date = date('Y-m-01');
        if (!$end_date) $end_date = date('Y-m-t');
        
        // Revenue
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'paid'");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $revenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        
        // Expenses
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $expenses = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        
        // Payroll (if implemented)
        $stmt = $this->conn->prepare("SELECT SUM(net_salary) as total FROM payroll WHERE payment_date BETWEEN ? AND ? AND status = 'paid'");
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $payroll = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        
        $total_expenses = $expenses + $payroll;
        $net_profit = $revenue - $total_expenses;
        $profit_margin = $revenue > 0 ? ($net_profit / $revenue) * 100 : 0;
        
        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'payroll' => $payroll,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
            'profit_margin' => round($profit_margin, 2)
        ];
    }
    
    /**
     * Get dashboard summary
     */
    public function getDashboardSummary() {
        $today = date('Y-m-d');
        $current_month = date('Y-m');
        
        return [
            'total_members' => $this->getCount('members'),
            'active_members' => $this->getCount('members', ['status' => 'active']),
            'total_trainers' => $this->getCount('trainers'),
            'today_attendance' => $this->getCount('attendance', ['date' => $today]),
            'monthly_revenue' => $this->getMonthlyRevenue($current_month),
            'pending_payments' => $this->getCount('payments', ['status' => 'pending']),
            'expiring_soon' => $this->getExpiringMemberships(7),
            'low_stock_items' => $this->getLowStockCount()
        ];
    }
    
    private function getCount($table, $conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = '$value'";
            }
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['count'];
    }
    
    private function getMonthlyRevenue($month) {
        $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? AND status = 'paid'");
        $stmt->bind_param("s", $month);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        return $total;
    }
    
    private function getExpiringMemberships($days) {
        $expiry_date = date('Y-m-d', strtotime("+$days days"));
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM members WHERE expiry_date <= ? AND status = 'active'");
        $stmt->bind_param("s", $expiry_date);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        return $count;
    }
    
    private function getLowStockCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= 10");
        return $result->fetch_assoc()['count'];
    }
    
    /**
     * Export data to CSV
     */
    public function exportToCSV($data, $filename, $headers) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Generate PDF report (requires FPDF)
     */
    public function generatePDFReport($title, $data) {
        require_once('../fpdf/fpdf.php');
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        $pdf->Ln(10);
        
        // Add data to PDF (customize based on your needs)
        $pdf->SetFont('Arial', '', 12);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, ucfirst($key), 0, 1);
                $pdf->SetFont('Arial', '', 10);
                foreach ($value as $item) {
                    $pdf->Cell(0, 8, implode(' | ', $item), 0, 1);
                }
                $pdf->Ln(5);
            } else {
                $pdf->Cell(0, 8, ucfirst($key) . ': ' . $value, 0, 1);
            }
        }
        
        return $pdf->Output('D', str_replace(' ', '_', $title) . '.pdf');
    }
}

// Initialize analytics
$analytics = new Analytics($conn);
?>
