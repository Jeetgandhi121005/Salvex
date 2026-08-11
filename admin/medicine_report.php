<?php
session_start();
include 'includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Super Admin';
salvex_sync_billing_status($conn);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_medicine_expenses':
            $patient_id = $_GET['patient_id'] ?? '';
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            
            $sql = "SELECT m.*, p.full_name as patient_name, p.email as patient_email
                    FROM medicine_expenses m
                    LEFT JOIN users p ON m.patient_id = p.id
                    WHERE m.purchase_date BETWEEN '$from' AND '$to'";
            
            if ($patient_id) {
                $sql .= " AND m.patient_id = " . intval($patient_id);
            }
            
            $sql .= " ORDER BY m.purchase_date DESC, m.created_at DESC";
            $result = mysqli_query($conn, $sql);
            $expenses = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $expenses[] = $row;
            }
            
            // Calculate totals
            $total_amount = array_sum(array_column($expenses, 'amount'));
            $total_medicines = count($expenses);
            
            echo json_encode([
                'status' => 'success',
                'data' => $expenses,
                'summary' => [
                    'total_amount' => $total_amount,
                    'total_medicines' => $total_medicines,
                    'average_per_medicine' => $total_medicines > 0 ? round($total_amount / $total_medicines, 2) : 0
                ]
            ]);
            exit();
            
        case 'get_patient_summary':
            $patient_id = intval($_GET['patient_id'] ?? 0);
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            
            if (!$patient_id) {
                echo json_encode(['status' => 'error', 'message' => 'Patient ID required']);
                exit();
            }
            
            $sql = "SELECT 
                    COUNT(*) as total_medicines,
                    SUM(amount) as total_spent,
                    AVG(amount) as avg_spent,
                    MIN(purchase_date) as first_purchase,
                    MAX(purchase_date) as last_purchase
                    FROM medicine_expenses
                    WHERE patient_id = $patient_id
                    AND purchase_date BETWEEN '$from' AND '$to'";
            
            $result = mysqli_query($conn, $sql);
            $summary = mysqli_fetch_assoc($result);
            
            // Get patient details
            $patient_sql = "SELECT id, full_name as name, email, phone FROM users WHERE id = $patient_id";
            $patient_result = mysqli_query($conn, $patient_sql);
            $patient = mysqli_fetch_assoc($patient_result);
            
            // Get monthly breakdown
            $monthly_sql = "SELECT 
                            DATE_FORMAT(purchase_date, '%Y-%m') as month,
                            SUM(amount) as monthly_total,
                            COUNT(*) as medicine_count
                            FROM medicine_expenses
                            WHERE patient_id = $patient_id
                            GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
                            ORDER BY month DESC
                            LIMIT 6";
            
            $monthly_result = mysqli_query($conn, $monthly_sql);
            $monthly_data = [];
            while ($row = mysqli_fetch_assoc($monthly_result)) {
                $monthly_data[] = $row;
            }
            
            echo json_encode([
                'status' => 'success',
                'patient' => $patient,
                'summary' => $summary,
                'monthly_breakdown' => $monthly_data
            ]);
            exit();
            
        case 'get_all_patients_summary':
            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                    p.id,
                    p.full_name as name,
                    p.email,
                    COUNT(m.id) as total_medicines,
                    SUM(m.amount) as total_spent,
                    AVG(m.amount) as avg_spent,
                    MAX(m.purchase_date) as last_purchase
                    FROM users p
                    LEFT JOIN medicine_expenses m ON p.id = m.patient_id
                    AND m.purchase_date BETWEEN '$from' AND '$to'
                    GROUP BY p.id
                    HAVING total_spent > 0
                    ORDER BY total_spent DESC";
            
            $result = mysqli_query($conn, $sql);
            $patients = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $patients[] = $row;
            }
            
            $grand_total = array_sum(array_column($patients, 'total_spent'));
            
            echo json_encode([
                'status' => 'success',
                'data' => $patients,
                'summary' => [
                    'total_patients' => count($patients),
                    'grand_total' => $grand_total,
                    'average_per_patient' => count($patients) > 0 ? round($grand_total / count($patients), 2) : 0
                ]
            ]);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Expense Report - Salvex Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300,400,500,600,700,800,900&family=Poppins:wght@400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        .medicine-page { padding: 24px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0; }
        .page-subtitle { font-size: 14px; color: #64748b; margin-top: 4px; }
        .report-tabs { display: flex; gap: 8px; margin-bottom: 24px; background: #fff; padding: 8px; border-radius: 12px; border: 1px solid #e2e8f0; width: fit-content; }
        .report-tab { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; background: transparent; color: #64748b; transition: all 0.2s; }
        .report-tab:hover { background: #f1f5f9; }
        .report-tab.active { background: #0080FF; color: #fff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 12px; }
        .stat-icon.blue { background: #dbeafe; color: #1e40af; }
        .stat-icon.green { background: #d1fae5; color: #065f46; }
        .stat-icon.purple { background: #f3e8ff; color: #7c3aed; }
        .stat-icon.orange { background: #fef3c7; color: #92400e; }
        .stat-value { font-size: 24px; font-weight: 700; color: #1a1a1a; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
        .filter-bar { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; }
        .filter-select, .filter-input { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #fff; color: #1a1a1a; min-width: 150px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #0080FF; color: #fff; }
        .btn-primary:hover { background: #0066cc; }
        .btn-success { background: #10B981; color: #fff; }
        .btn-success:hover { background: #059669; }
        .content-card { background: #fff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .data-table th, .data-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .data-table th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #475569; }
        .data-table tr:hover { background: #f8fafc; }
        .patient-select-dropdown { min-width: 250px; }
        .chart-container { height: 300px; margin: 20px 0; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .medicine-name { font-weight: 600; color: #1a1a1a; }
        .medicine-desc { font-size: 13px; color: #64748b; margin-top: 2px; }
        .amount-cell { font-weight: 700; color: #10B981; font-size: 15px; }
        @media (max-width: 768px) {
            .data-table { overflow-x: auto; }
            .filter-bar { flex-direction: column; }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dash-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebarToggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            </div>
            <div class="sidebar-admin-info">
                <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
                <div class="admin-details">
                    <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="admin-role"><i class="fas fa-circle" style="color:#10B981;font-size:8px"></i> Online</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section-label">Main</div>
                <a href="dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
                <a href="appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
                <div class="nav-section-label">Reports</div>
                <a href="reports.php" class="nav-item"><i class="fas fa-file-chart-column"></i><span>All Reports</span></a>
                <a href="medicine_report.php" class="nav-item active"><i class="fas fa-pills"></i><span>Medicine Expenses</span></a>
                <div class="nav-section-label">System</div>
                <a href="login.php" class="nav-item nav-logout"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
            </nav>
        </aside>
        
        <main class="dash-main">
            <div class="medicine-page">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Medicine Expense Report</h1>
                        <p class="page-subtitle">Track and analyze patient medicine spending</p>
                    </div>
                </div>
                
                <div class="report-tabs">
                    <button class="report-tab active" onclick="switchTab('all')">
                        <i class="fas fa-users"></i> All Patients
                    </button>
                    <button class="report-tab" onclick="switchTab('patient')">
                        <i class="fas fa-user"></i> Individual Patient
                    </button>
                </div>
                
                <div class="filter-bar" id="allPatientsFilter">
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" class="filter-input" id="filterFrom" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" class="filter-input" id="filterTo" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button class="btn btn-primary" onclick="loadAllPatientsData()">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <button class="btn btn-success" onclick="exportToCSV()" id="exportBtn" style="display:none;">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
                
                <div class="filter-bar" id="patientFilter" style="display:none;">
                    <div class="filter-group">
                        <label class="filter-label">Select Patient</label>
                        <select class="filter-select patient-select-dropdown" id="patientSelect" onchange="loadPatientData()">
                            <option value="">-- Select a Patient --</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" class="filter-input" id="patientFrom" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" class="filter-input" id="patientTo" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button class="btn btn-primary" onclick="loadPatientData()">
                        <i class="fas fa-search"></i> View Report
                    </button>
                </div>
                
                <div id="statsContainer"></div>
                
                <div class="content-card" id="resultsCard">
                    <div class="empty-state">
                        <i class="fas fa-pills"></i>
                        <p>Select a date range and click Generate Report to view medicine expenses</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let currentTab = 'all';
        let currentData = [];
        let chartInstance = null;
        let allPatients = [];
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        }
        
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            if (tab === 'all') {
                document.getElementById('allPatientsFilter').style.display = 'flex';
                document.getElementById('patientFilter').style.display = 'none';
                document.getElementById('statsContainer').innerHTML = '';
                document.getElementById('resultsCard').innerHTML = `
                    <div class="empty-state"><i class="fas fa-pills"></i><p>Select a date range and click Generate Report to view medicine expenses</p></div>
                `;
            } else {
                document.getElementById('allPatientsFilter').style.display = 'none';
                document.getElementById('patientFilter').style.display = 'flex';
                document.getElementById('statsContainer').innerHTML = '';
                document.getElementById('resultsCard').innerHTML = `
                    <div class="empty-state"><i class="fas fa-user"></i><p>Select a patient to view their medicine expense history</p></div>
                `;
                loadPatientDropdown();
            }
        }
        
        async function loadPatientDropdown() {
            try {
                const from = document.getElementById('patientFrom').value;
                const to = document.getElementById('patientTo').value;
                const response = await fetch(`medicine_report.php?action=get_all_patients_summary&from=${from}&to=${to}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    allPatients = result.data;
                    const select = document.getElementById('patientSelect');
                    select.innerHTML = '<option value="">-- Select a Patient --</option>' +
                        result.data.map(p => `<option value="${p.id}">${p.name} (Spent: ₹${p.total_spent})</option>`).join('');
                }
            } catch (error) {
                console.error('Error loading patients:', error);
            }
        }
        
        async function loadAllPatientsData() {
            const from = document.getElementById('filterFrom').value;
            const to = document.getElementById('filterTo').value;
            
            const resultsCard = document.getElementById('resultsCard');
            resultsCard.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading data...</p></div>';
            
            try {
                const response = await fetch(`medicine_report.php?action=get_all_patients_summary&from=${from}&to=${to}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    currentData = result.data;
                    renderAllPatientsStats(result.summary);
                    renderAllPatientsTable(result.data);
                    document.getElementById('exportBtn').style.display = 'inline-flex';
                }
            } catch (error) {
                resultsCard.innerHTML = '<div class="empty-state"><p>Error loading data</p></div>';
            }
        }
        
        function renderAllPatientsStats(summary) {
            const container = document.getElementById('statsContainer');
            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        <div class="stat-value">${summary.total_patients}</div>
                        <div class="stat-label">Patients with Purchases</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
                        <div class="stat-value">₹${summary.grand_total.toLocaleString()}</div>
                        <div class="stat-label">Total Medicine Spending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-calculator"></i></div>
                        <div class="stat-value">₹${summary.average_per_patient.toLocaleString()}</div>
                        <div class="stat-label">Avg per Patient</div>
                    </div>
                </div>
            `;
        }
        
        function renderAllPatientsTable(data) {
            const resultsCard = document.getElementById('resultsCard');
            
            if (data.length === 0) {
                resultsCard.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No medicine expenses found for selected period</p></div>';
                return;
            }
            
            resultsCard.innerHTML = `
                <h3 style="margin-bottom:16px;font-size:16px;color:#1a1a1a;"><i class="fas fa-table" style="margin-right:8px"></i>Patient-wise Medicine Spending</h3>
                <table class="data-table">
                    <thead><tr>
                        <th>Patient Name</th>
                        <th>Email</th>
                        <th>Total Medicines</th>
                        <th>Total Spent</th>
                        <th>Avg per Medicine</th>
                        <th>Last Purchase</th>
                    </tr></thead>
                    <tbody>${data.map(p => `
                        <tr>
                            <td><strong>${p.name}</strong></td>
                            <td>${p.email || 'N/A'}</td>
                            <td>${p.total_medicines || 0}</td>
                            <td class="amount-cell">₹${(p.total_spent || 0).toLocaleString()}</td>
                            <td>₹${(p.avg_spent || 0).toFixed(2)}</td>
                            <td>${p.last_purchase || 'N/A'}</td>
                        </tr>
                    `).join('')}</tbody>
                </table>
            `;
        }
        
        async function loadPatientData() {
            const patientId = document.getElementById('patientSelect').value;
            const from = document.getElementById('patientFrom').value;
            const to = document.getElementById('patientTo').value;
            
            if (!patientId) {
                alert('Please select a patient');
                return;
            }
            
            const resultsCard = document.getElementById('resultsCard');
            resultsCard.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading patient data...</p></div>';
            
            try {
                const response = await fetch(`medicine_report.php?action=get_patient_summary&patient_id=${patientId}&from=${from}&to=${to}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    renderPatientReport(result);
                }
            } catch (error) {
                resultsCard.innerHTML = '<div class="empty-state"><p>Error loading data</p></div>';
            }
        }
        
        function renderPatientReport(result) {
            const statsContainer = document.getElementById('statsContainer');
            const resultsCard = document.getElementById('resultsCard');
            const summary = result.summary || {};
            const patient = result.patient || {};
            
            statsContainer.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-user"></i></div>
                        <div class="stat-value">${patient.name || 'N/A'}</div>
                        <div class="stat-label">Patient Name</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-pills"></i></div>
                        <div class="stat-value">${summary.total_medicines || 0}</div>
                        <div class="stat-label">Total Medicines</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-rupee-sign"></i></div>
                        <div class="stat-value">₹${(summary.total_spent || 0).toLocaleString()}</div>
                        <div class="stat-label">Total Spent (This Period)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-calculator"></i></div>
                        <div class="stat-value">₹${(summary.avg_spent || 0).toFixed(2)}</div>
                        <div class="stat-label">Avg per Medicine</div>
                    </div>
                </div>
            `;
            
            resultsCard.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="font-size:16px;color:#1a1a1a;"><i class="fas fa-chart-line" style="margin-right:8px"></i>Monthly Spending Trend</h3>
                </div>
                <div class="chart-container"><canvas id="patientChart"></canvas></div>
            `;
            
            setTimeout(() => renderPatientChart(result.monthly_breakdown || []), 100);
        }
        
        function renderPatientChart(monthlyData) {
            const ctx = document.getElementById('patientChart');
            if (!ctx) return;
            
            if (chartInstance) chartInstance.destroy();
            
            const labels = monthlyData.map(d => d.month).reverse();
            const data = monthlyData.map(d => d.monthly_total).reverse();
            
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Medicine Spending (₹)',
                        data: data,
                        backgroundColor: 'rgba(16,185,129,0.7)',
                        borderColor: '#10B981',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '₹' + value
                            }
                        }
                    }
                }
            });
        }
        
        function exportToCSV() {
            if (currentData.length === 0) {
                alert('No data to export');
                return;
            }
            
            let csv = 'Patient Name,Email,Total Medicines,Total Spent,Avg per Medicine,Last Purchase\n';
            currentData.forEach(p => {
                csv += `"${p.name}","${p.email || ''}",${p.total_medicines || 0},${p.total_spent || 0},${(p.avg_spent || 0).toFixed(2)},"${p.last_purchase || ''}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'medicine_expenses_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', () => {
            // Optionally auto-load data
        });
    </script>
</body>
</html>
