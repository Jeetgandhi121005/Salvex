<?php
session_start();
include 'includes/db.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Super Admin';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_appointments':
            $status_filter = $_GET['status'] ?? 'all';
            $date_from = $_GET['from'] ?? '';
            $date_to = $_GET['to'] ?? '';
            
            $sql = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone,
                    d.name as doctor_name, d.specialty, d.email as doctor_email, d.phone as doctor_phone
                    FROM appointments a
                    LEFT JOIN patients p ON a.user_id = p.id
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    WHERE 1=1";
            
            if ($status_filter !== 'all') {
                $sql .= " AND a.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
            }
            if ($date_from) {
                $sql .= " AND a.appointment_date >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
            }
            if ($date_to) {
                $sql .= " AND a.appointment_date <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
            }
            
            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            $result = mysqli_query($conn, $sql);
            $appointments = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $appointments[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $appointments]);
            exit();
            
        case 'update_status':
            $id = intval($_POST['id'] ?? 0);
            $status = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
            
            if ($id && $status) {
                $sql = "UPDATE appointments SET status = '$status' WHERE id = $id";
                if (mysqli_query($conn, $sql)) {
                    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            }
            exit();
            
        case 'delete_appointment':
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $sql = "DELETE FROM appointments WHERE id = $id";
                if (mysqli_query($conn, $sql)) {
                    echo json_encode(['status' => 'success', 'message' => 'Appointment deleted successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete appointment']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid appointment ID']);
            }
            exit();
            
        case 'get_stats':
            $today = date('Y-m-d');
            $stats = [];
            
            // Total appointments
            $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments");
            $stats['total'] = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            // Today's appointments
            $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
            $stats['today'] = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            // By status
            $r = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
            while ($row = mysqli_fetch_assoc($r)) {
                $stats[strtolower($row['status'])] = $row['count'];
            }
            
            // Pending appointments
            $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE status = 'Pending'");
            $stats['pending'] = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            echo json_encode(['status' => 'success', 'data' => $stats]);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - Salvex Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300,400,500,600,700,800,900&family=Poppins:wght@400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .appointments-page { padding: 24px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0; }
        .page-subtitle { font-size: 14px; color: #64748b; margin-top: 4px; }
        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; }
        .filter-select, .filter-input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #fff; color: #1a1a1a; min-width: 150px; }
        .btn { padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: #0080FF; color: #fff; }
        .btn-primary:hover { background: #0066cc; }
        .btn-success { background: #10B981; color: #fff; }
        .btn-warning { background: #F59E0B; color: #fff; }
        .btn-danger { background: #F43F5E; color: #fff; }
        .btn-secondary { background: #64748b; color: #fff; }
        .appointments-table { width: 100%; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .appointments-table table { width: 100%; border-collapse: collapse; }
        .appointments-table th, .appointments-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .appointments-table th { background: #f8fafc; font-weight: 600; font-size: 12px; text-transform: uppercase; color: #475569; }
        .appointments-table tr:hover { background: #f8fafc; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .action-btns { display: flex; gap: 6px; }
        .action-btn { padding: 6px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; border: none; transition: all 0.2s; }
        .action-btn-edit { background: #dbeafe; color: #1e40af; }
        .action-btn-delete { background: #fee2e2; color: #991b1b; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .stat-value { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        @media (max-width: 768px) {
            .appointments-table { overflow-x: auto; }
            .filter-bar { flex-direction: column; }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="dash-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="sidebar-toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-gauge-high"></i><span>Dashboard</span>
                </a>
                <a href="appointments.php" class="nav-item active">
                    <i class="fas fa-calendar-check"></i><span>Appointments</span>
                </a>
                <a href="#doctors" class="nav-item">
                    <i class="fas fa-user-doctor"></i><span>Doctors</span>
                </a>
                <a href="#patients" class="nav-item">
                    <i class="fas fa-users"></i><span>Patients</span>
                </a>
                <div class="nav-section-label">Reports</div>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-chart-column"></i><span>All Reports</span>
                </a>
                <a href="medicine_report.php" class="nav-item">
                    <i class="fas fa-pills"></i><span>Medicine Expenses</span>
                </a>
                <div class="nav-section-label">System</div>
                <a href="login.php" class="nav-item nav-logout">
                    <i class="fas fa-right-from-bracket"></i><span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <main class="dash-main">
            <div class="appointments-page">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Manage Appointments</h1>
                        <p class="page-subtitle">View, filter, and manage all patient appointments</p>
                    </div>
                    <button class="btn btn-primary" onclick="loadAppointments()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <div class="stats-row" id="statsRow">
                    <div class="stat-card">
                        <div class="stat-value" id="statTotal">0</div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statToday">0</div>
                        <div class="stat-label">Today's Appointments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statPending">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statConfirmed">0</div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                </div>
                
                <div class="filter-bar">
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select class="filter-select" id="filterStatus" onchange="loadAppointments()">
                            <option value="all">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">From Date</label>
                        <input type="date" class="filter-input" id="filterFrom" onchange="loadAppointments()">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">To Date</label>
                        <input type="date" class="filter-input" id="filterTo" onchange="loadAppointments()">
                    </div>
                    <div class="filter-group" style="justify-content: flex-end;">
                        <button class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="appointments-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTableBody">
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading appointments...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let allAppointments = [];
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
        }
        
        async function loadAppointments() {
            const status = document.getElementById('filterStatus').value;
            const from = document.getElementById('filterFrom').value;
            const to = document.getElementById('filterTo').value;
            
            try {
                const response = await fetch(`appointments.php?action=get_appointments&status=${status}&from=${from}&to=${to}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    allAppointments = result.data;
                    renderTable(result.data);
                    loadStats();
                }
            } catch (error) {
                console.error('Error loading appointments:', error);
            }
        }
        
        function renderTable(appointments) {
            const tbody = document.getElementById('appointmentsTableBody');
            
            if (appointments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><i class="fas fa-inbox"></i><p>No appointments found</p></td></tr>';
                return;
            }
            
            tbody.innerHTML = appointments.map(apt => {
                const patientName = apt.patient_name || 'N/A';
                const doctorName = apt.doctor_name || apt.doctor_name || 'N/A';
                const specialty = apt.specialty || 'N/A';
                const statusClass = 'status-' + apt.status.toLowerCase();
                
                return '<tr>' +
                    '<td>#' + (apt.id || 'N/A') + '</td>' +
                    '<td>' + patientName + '</td>' +
                    '<td>' + doctorName + '</td>' +
                    '<td>' + specialty + '</td>' +
                    '<td>' + (apt.appointment_date ? new Date(apt.appointment_date).toLocaleDateString() : 'N/A') + '</td>' +
                    '<td>' + (apt.appointment_time || 'N/A') + '</td>' +
                    '<td><span class="status-badge ' + statusClass + '">' + (apt.status || 'Unknown') + '</span></td>' +
                    '<td><div class="action-btns">' +
                        '<button class="action-btn action-btn-edit" onclick="updateStatus(' + apt.id + ', \'Confirmed\')"><i class="fas fa-check"></i></button>' +
                        '<button class="action-btn action-btn-delete" onclick="deleteAppointment(' + apt.id + ')"><i class="fas fa-trash"></i></button>' +
                    '</div></td>' +
                '</tr>';
            }).join('');
        }
        
        async function loadStats() {
            try {
                const response = await fetch('appointments.php?action=get_stats');
                const result = await response.json();
                
                if (result.status === 'success') {
                    const stats = result.data;
                    document.getElementById('statTotal').textContent = stats.total || 0;
                    document.getElementById('statToday').textContent = stats.today || 0;
                    document.getElementById('statPending').textContent = stats.pending || 0;
                    document.getElementById('statConfirmed').textContent = stats.confirmed || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        async function updateStatus(id, status) {
            if (!confirm('Change status to ' + status + '?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            
            try {
                const response = await fetch('appointments.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Status updated successfully!');
                    loadAppointments();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error updating status');
            }
        }
        
        async function deleteAppointment(id) {
            if (!confirm('Are you sure you want to delete this appointment?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            try {
                const response = await fetch('appointments.php?action=delete_appointment', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('Appointment deleted successfully!');
                    loadAppointments();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error deleting appointment');
            }
        }
        
        function exportToCSV() {
            if (allAppointments.length === 0) {
                alert('No appointments to export');
                return;
            }
            
            let csv = 'ID,Patient,Doctor,Specialty,Date,Time,Status\n';
            allAppointments.forEach(apt => {
                csv += apt.id + ',' +
                    '"' + (apt.patient_name || '') + '",' +
                    '"' + (apt.doctor_name || '') + '",' +
                    '"' + (apt.specialty || '') + '",' +
                    apt.appointment_date + ',' +
                    apt.appointment_time + ',' +
                    apt.status + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'appointments_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Load on page load
        document.addEventListener('DOMContentLoaded', loadAppointments);
    </script>
</body>
</html>
