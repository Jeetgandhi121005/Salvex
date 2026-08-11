const DOCTORS = (window.ADMIN_DATA && window.ADMIN_DATA.doctors) || [];
const PATIENTS = (window.ADMIN_DATA && window.ADMIN_DATA.patients) || [];
const APPOINTMENTS = (window.ADMIN_DATA && window.ADMIN_DATA.appointments) || [];
const HOSPITALS = (window.ADMIN_DATA && window.ADMIN_DATA.hospitals) || [];
const searchData = (window.ADMIN_DATA && window.ADMIN_DATA.searchData) || [];
const chartData = (window.ADMIN_DATA && window.ADMIN_DATA.charts) || {};

function postAdminAction(formData) {
    return fetch('admin_action.php', { method: 'POST', body: formData }).then((res) => res.json());
}

document.addEventListener('DOMContentLoaded', function () {
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Date().toLocaleDateString('en-IN', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    if (localStorage.getItem('salvex_admin_dark') === '1') {
        document.body.classList.add('dark');
        const icon = document.querySelector('#darkToggle i');
        if (icon) icon.className = 'fas fa-sun';
        const toggle = document.getElementById('darkModeToggle');
        if (toggle) toggle.checked = true;
    }

    initAppointmentChart();
    initSpecializationChart();

    document.addEventListener('click', function (e) {
        const profileMenu = document.getElementById('profileMenu');
        if (profileMenu && !e.target.closest('.topbar-profile')) {
            profileMenu.classList.remove('open');
        }

        const dropdown = document.getElementById('searchDropdown');
        if (dropdown && !e.target.closest('.topbar-search')) {
            dropdown.classList.remove('show');
        }
    });
});

function switchSection(name, linkEl) {
    document.querySelectorAll('.dash-section').forEach((section) => section.classList.remove('active'));
    const target = document.getElementById('section-' + name);
    if (target) target.classList.add('active');

    document.querySelectorAll('.nav-item').forEach((link) => link.classList.remove('active'));
    if (linkEl && linkEl.classList) {
        linkEl.classList.add('active');
    } else {
        const autoLink = document.querySelector('[href="#' + name + '"]');
        if (autoLink) autoLink.classList.add('active');
    }

    document.getElementById('sidebar')?.classList.remove('mobile-open');

    if (name === 'analytics') {
        setTimeout(function () {
            initWeeklyChart();
            initRegChart();
            initPeakChart();
        }, 100);
    }

    return false;
}

function toggleSidebar() {
    document.querySelector('.dash-layout')?.classList.toggle('sidebar-collapsed');
    document.getElementById('sidebar')?.classList.toggle('collapsed');
}

function toggleMobileSidebar() {
    document.getElementById('sidebar')?.classList.toggle('mobile-open');
}

function toggleCompactSidebar() {
    toggleSidebar();
}

function toggleDarkMode() {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    const icon = document.querySelector('#darkToggle i');
    const toggle = document.getElementById('darkModeToggle');

    if (icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    if (toggle) toggle.checked = isDark;

    localStorage.setItem('salvex_admin_dark', isDark ? '1' : '0');

    setTimeout(function () {
        initAppointmentChart();
        initSpecializationChart();
        initWeeklyChart();
        initRegChart();
        initPeakChart();
    }, 100);
}

function toggleProfileMenu() {
    document.getElementById('profileMenu')?.classList.toggle('open');
}

function handleSearch(val) {
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) return;

    if (!val.trim()) {
        dropdown.classList.remove('show');
        return;
    }

    const filtered = searchData.filter((item) => item.label.toLowerCase().includes(val.toLowerCase()));
    if (!filtered.length) {
        dropdown.innerHTML = '<div class="search-result-item"><i class="fas fa-circle-xmark"></i> No results found</div>';
    } else {
        dropdown.innerHTML = filtered.slice(0, 7).map((item) => {
            return `<div class="search-result-item" onclick="switchSection('${item.section}');document.getElementById('globalSearch').value='';document.getElementById('searchDropdown').classList.remove('show');"><i class="fas ${item.icon}"></i> ${highlight(item.label, val)}</div>`;
        }).join('');
    }

    dropdown.classList.add('show');
}

function highlight(text, query) {
    const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return text.replace(re, '<strong style="color:var(--primary)">$1</strong>');
}

function filterTable(tableId, query) {
    const table = document.getElementById(tableId);
    if (!table) return;

    table.querySelectorAll('tbody tr').forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(query.toLowerCase()) ? '' : 'none';
    });
}

function filterByStatus(tableId, status, colIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;

    table.querySelectorAll('tbody tr').forEach((row) => {
        const cell = row.cells[colIndex];
        row.style.display = (!status || (cell && cell.textContent.includes(status))) ? '' : 'none';
    });
}

function filterByDate(dateVal) {
    if (!dateVal) {
        filterTable('apptTable', '');
        return;
    }

    const date = new Date(dateVal);
    const formatted = date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, ' ');
    filterTable('apptTable', formatted);
}

function toggleDoctor(btn, id, name) {
    const row = btn.closest('tr');
    const pill = row?.querySelector('.status-pill');
    if (!pill) return;

    const currentlyActive = pill.textContent.trim() === 'Active';
    const nextActive = currentlyActive ? 0 : 1;

    const fd = new FormData();
    fd.append('action', 'toggle_doctor');
    fd.append('doctor_id', id);
    fd.append('is_active', nextActive);

    postAdminAction(fd).then((data) => {
        if (data.status !== 'success') {
            showToast(data.message || 'Could not update doctor status.', 'error');
            return;
        }

        pill.className = 'status-pill ' + (nextActive ? 'pill-active' : 'pill-inactive');
        pill.textContent = nextActive ? 'Active' : 'Inactive';
        btn.className = nextActive ? 'btn-disable' : 'btn-enable';
        btn.innerHTML = nextActive
            ? '<i class="fas fa-ban"></i> Disable'
            : '<i class="fas fa-check"></i> Enable';
        showToast(name + (nextActive ? ' enabled.' : ' disabled.'), nextActive ? 'success' : 'warning');
    }).catch(() => showToast('Could not update doctor status.', 'error'));
}

function toggleHospital(btn, id, name) {
    const card = btn.closest('.hospital-card');
    const pill = card?.querySelector('.status-pill');
    if (!pill) return;

    const currentlyActive = pill.textContent.trim() === 'Active';
    const nextActive = currentlyActive ? 0 : 1;

    const fd = new FormData();
    fd.append('action', 'toggle_hospital');
    fd.append('hospital_id', id);
    fd.append('is_active', nextActive);

    postAdminAction(fd).then((data) => {
        if (data.status !== 'success') {
            showToast(data.message || 'Could not update hospital status.', 'error');
            return;
        }

        pill.className = 'status-pill ' + (nextActive ? 'pill-active' : 'pill-inactive');
        pill.textContent = nextActive ? 'Active' : 'Inactive';
        btn.className = nextActive ? 'btn-disable' : 'btn-enable';
        btn.innerHTML = nextActive
            ? '<i class="fas fa-ban"></i> Disable'
            : '<i class="fas fa-check"></i> Enable';
        showToast(name + (nextActive ? ' activated.' : ' deactivated.'), nextActive ? 'success' : 'warning');
    }).catch(() => showToast('Could not update hospital status.', 'error'));
}

function updateApptStatus(id, newStatus) {
    const appt = APPOINTMENTS.find((item) => item.id === id);
    if (!appt) return;

    const fd = new FormData();
    fd.append('action', 'update_appointment');
    fd.append('appointment_id', appt.db_id);
    fd.append('new_status', newStatus);

    postAdminAction(fd).then((data) => {
        if (data.status === 'success') {
            showToast(`Appointment ${id} marked as ${newStatus}.`, 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Could not update appointment.', 'error');
        }
    }).catch(() => showToast('Could not update appointment.', 'error'));
}

function viewDoctor(id) {
    const doctor = DOCTORS.find((item) => item.id === id);
    if (!doctor) return;

    const statusClass = doctor.status === 'Active' ? 'pill-active' : 'pill-inactive';
    const actionButton = doctor.status === 'Active'
        ? `<button class="btn-disable" style="flex:1;padding:10px;justify-content:center" onclick="closeModal();setTimeout(() => {
                const rowBtn = document.querySelector('tr[data-doctor-id=\"${doctor.id}\"] .btn-disable');
                if (rowBtn) toggleDoctor(rowBtn, ${doctor.id}, '${doctor.name.replace(/'/g, "\\'")}');
            }, 50)"><i class="fas fa-ban"></i> Disable Account</button>`
        : `<button class="btn-enable" style="flex:1;padding:10px;justify-content:center" onclick="closeModal();setTimeout(() => {
                const rowBtn = document.querySelector('tr[data-doctor-id=\"${doctor.id}\"] .btn-enable');
                if (rowBtn) toggleDoctor(rowBtn, ${doctor.id}, '${doctor.name.replace(/'/g, "\\'")}');
            }, 50)"><i class="fas fa-check"></i> Enable Account</button>`;

    showModal(`
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
            <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#0080FF,#818CF8);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;flex-shrink:0">${doctor.initials}</div>
            <div>
                <h3 style="font-size:20px;font-weight:800;margin-bottom:6px">${doctor.name}</h3>
                <span class="status-pill ${statusClass}">${doctor.status}</span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            ${infoRow('Specialization', doctor.spec, 'fa-stethoscope')}
            ${infoRow('Hospital', doctor.hosp, 'fa-hospital')}
            ${infoRow('Email', doctor.email, 'fa-envelope')}
            ${infoRow('Phone', doctor.phone || 'N/A', 'fa-phone')}
            ${infoRow('Experience', doctor.exp, 'fa-clock')}
            ${infoRow('Total Patients', String(doctor.patients), 'fa-users')}
            ${infoRow('Schedule', doctor.schedule, 'fa-calendar')}
        </div>
        <div style="display:flex;gap:10px;margin-top:18px">
            <button class="btn-view" style="flex:1;padding:10px;justify-content:center" onclick="showToast('Contact details copied.', 'success')"><i class="fas fa-envelope"></i> Contact</button>
            ${actionButton}
        </div>
    `);
}

function viewPatient(id) {
    const patient = PATIENTS.find((item) => item.id === id);
    if (!patient) return;

    showModal(`
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
            <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#818CF8,#F43F5E);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#fff;flex-shrink:0">${patient.initials}</div>
            <div>
                <h3 style="font-size:20px;font-weight:800;margin-bottom:6px">${patient.name}</h3>
                <span class="status-pill pill-active">Patient</span>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            ${infoRow('Age', patient.age ? `${patient.age} years` : 'Not available', 'fa-user')}
            ${infoRow('Email', patient.email, 'fa-envelope')}
            ${infoRow('Phone', patient.phone || 'N/A', 'fa-phone')}
            ${infoRow('Last Visit', patient.lastVisit, 'fa-calendar-check')}
            ${infoRow('Appointments', `${patient.appts}`, 'fa-calendar')}
        </div>
    `);
}

function viewAppt(id) {
    const appt = APPOINTMENTS.find((item) => item.id === id);
    if (!appt) return;

    const statusClass = {
        Confirmed: 'pill-confirmed',
        Pending: 'pill-pending',
        Completed: 'pill-completed',
        Cancelled: 'pill-cancelled'
    }[appt.status] || 'pill-pending';

    const pendingActions = appt.status === 'Pending'
        ? `<div style="display:flex;gap:10px;margin-top:18px">
                <button class="btn-enable" style="flex:1;padding:10px;justify-content:center" onclick="closeModal();updateApptStatus('${appt.id}','Confirmed')"><i class="fas fa-check"></i> Confirm</button>
                <button class="btn-disable" style="flex:1;padding:10px;justify-content:center" onclick="closeModal();updateApptStatus('${appt.id}','Cancelled')"><i class="fas fa-xmark"></i> Cancel</button>
           </div>`
        : '';

    showModal(`
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
            <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#10B981,#0080FF);display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff"><i class="fas fa-calendar-check"></i></div>
            <div><h3 style="font-size:20px;font-weight:800;margin-bottom:5px">Appointment ${appt.id}</h3><span class="status-pill ${statusClass}">${appt.status}</span></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            ${infoRow('Patient', appt.patient, 'fa-user')}
            ${infoRow('Doctor', appt.doctor, 'fa-user-doctor')}
            ${infoRow('Hospital', appt.hosp, 'fa-hospital')}
            ${infoRow('Department', appt.dept, 'fa-stethoscope')}
            ${infoRow('Date', appt.date, 'fa-calendar')}
            ${infoRow('Time', appt.time, 'fa-clock')}
        </div>
        <div style="margin-top:14px;padding:12px 14px;background:var(--surface);border:1px solid var(--border);border-radius:10px">
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:5px"><i class="fas fa-note-sticky" style="color:var(--primary)"></i> Notes</div>
            <div style="font-size:14px;font-weight:500">${appt.notes}</div>
        </div>
        ${pendingActions}
    `);
}

function viewHospital(id) {
    const hospital = HOSPITALS.find((item) => item.id === id);
    if (!hospital) return;

    const statusClass = hospital.status === 'Active' ? 'pill-active' : 'pill-inactive';
    showModal(`
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
            <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#F59E0B,#EF4444);display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff"><i class="fas fa-hospital"></i></div>
            <div><h3 style="font-size:18px;font-weight:800;margin-bottom:5px">${hospital.name}</h3><span class="status-pill ${statusClass}">${hospital.status}</span></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            ${infoRow('Location', hospital.city, 'fa-location-dot')}
            ${infoRow('Doctors', `${hospital.doctors}`, 'fa-user-doctor')}
            ${infoRow('Patients', `${hospital.patients}`, 'fa-users')}
            ${infoRow('Appointments', `${hospital.appts}`, 'fa-calendar-check')}
        </div>
    `);
}

function infoRow(label, value, icon) {
    return `<div style="padding:12px 14px;background:var(--surface);border-radius:10px;border:1px solid var(--border)">
        <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:5px"><i class="fas ${icon}" style="color:var(--primary)"></i> ${label}</div>
        <div style="font-size:14px;font-weight:600;color:var(--text-primary)">${value}</div>
    </div>`;
}

function showModal(html) {
    document.getElementById('modalContent').innerHTML = html;
    document.getElementById('infoModal').classList.add('open');
}

function closeModal(e) {
    if (!e || e.target === document.getElementById('infoModal')) {
        document.getElementById('infoModal').classList.remove('open');
    }
}

function resolveAlert(btn) {
    const item = btn.closest('.alert-item');
    if (!item) return;

    item.style.transition = 'all 0.4s ease';
    item.style.opacity = '0';
    item.style.transform = 'translateX(40px)';
    setTimeout(() => item.remove(), 400);
    showToast('Alert resolved.', 'success');
}

function markAllRead() {
    document.querySelectorAll('.alert-item').forEach((item, index) => {
        setTimeout(() => {
            item.style.transition = 'all 0.4s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(40px)';
            setTimeout(() => item.remove(), 400);
        }, index * 120);
    });
    showToast('All alerts cleared.', 'success');
}

function saveAdminProfile() {
    const name = document.getElementById('adminName')?.value || '';
    const email = document.getElementById('adminEmail')?.value || '';

    const fd = new FormData();
    fd.append('action', 'save_profile');
    fd.append('name', name);
    fd.append('email', email);

    postAdminAction(fd).then((data) => {
        if (data.status === 'success') {
            document.querySelector('.profile-name').textContent = name;
            document.querySelector('.profile-email').textContent = email;
            document.querySelector('.admin-name').textContent = name;
            showToast('Admin profile updated.', 'success');
        } else {
            showToast(data.message || 'Could not save profile.', 'error');
        }
    }).catch(() => showToast('Could not save profile.', 'error'));
}

function refreshData() {
    const icon = document.querySelector('.btn-refresh i');
    if (icon) {
        icon.style.animation = 'spin 0.7s linear';
        setTimeout(() => { icon.style.animation = ''; }, 700);
    }
    showToast('Refreshing dashboard...', 'info');
    setTimeout(() => window.location.reload(), 400);
}

function showToast(message, type) {
    type = type || 'info';
    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;margin-left:auto;padding:0 0 0 8px;opacity:0.7;font-size:14px">&#x2715;</button>`;
    container.appendChild(toast);

    setTimeout(function () {
        toast.classList.add('hiding');
        setTimeout(function () {
            if (toast.parentElement) toast.remove();
        }, 350);
    }, 3500);
}

function getChartColors() {
    const dark = document.body.classList.contains('dark');
    return {
        text: dark ? '#8B949E' : '#64748B',
        grid: dark ? '#21262D' : '#F1F5F9'
    };
}

let appointmentChartInst;
let specChartInst;
let weeklyChartInst;
let regChartInst;
let peakChartInst;

function destroyChart(inst) {
    try {
        if (inst) inst.destroy();
    } catch (error) {
        /* no-op */
    }
}

function initAppointmentChart() {
    destroyChart(appointmentChartInst);
    const ctx = document.getElementById('appointmentChart');
    if (!ctx) return;

    const colors = getChartColors();
    appointmentChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.months || [],
            datasets: [{
                label: 'Appointments',
                data: chartData.monthCounts || [],
                borderColor: '#0080FF',
                backgroundColor: 'rgba(0,128,255,0.15)',
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid }, beginAtZero: true }
            }
        }
    });
}

function initSpecializationChart() {
    destroyChart(specChartInst);
    const ctx = document.getElementById('specializationChart');
    if (!ctx) return;

    const palette = ['#0080FF', '#818CF8', '#10B981', '#F43F5E', '#F59E0B', '#06B6D4', '#7C3AED'];
    specChartInst = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartData.specialties || [],
            datasets: [{
                data: chartData.specialtyCounts || [],
                backgroundColor: (chartData.specialties || []).map((_, index) => palette[index % palette.length]),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    const legend = document.getElementById('specLegend');
    if (legend) {
        legend.innerHTML = (chartData.specialties || []).map((label, index) => {
            return `<div class="legend-item"><span class="legend-dot" style="background:${palette[index % palette.length]}"></span>${label}</div>`;
        }).join('');
    }
}

function initWeeklyChart() {
    destroyChart(weeklyChartInst);
    const ctx = document.getElementById('weeklyChart');
    if (!ctx) return;

    const colors = getChartColors();
    weeklyChartInst = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.weeklyLabels || [],
            datasets: [{
                data: chartData.weeklyCounts || [],
                backgroundColor: '#10B981'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid }, beginAtZero: true }
            }
        }
    });
}

function initRegChart() {
    destroyChart(regChartInst);
    const ctx = document.getElementById('regChart');
    if (!ctx) return;

    const colors = getChartColors();
    regChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.months || [],
            datasets: [{
                data: chartData.monthCounts || [],
                borderColor: '#818CF8',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid }, beginAtZero: true }
            }
        }
    });
}

function initPeakChart() {
    destroyChart(peakChartInst);
    const ctx = document.getElementById('peakChart');
    if (!ctx) return;

    const colors = getChartColors();
    peakChartInst = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.peakLabels || [],
            datasets: [{
                data: chartData.peakCounts || [],
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245,158,11,0.15)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: colors.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { color: colors.grid } },
                y: { ticks: { color: colors.text }, grid: { color: colors.grid }, beginAtZero: true }
            }
        }
    });
}
