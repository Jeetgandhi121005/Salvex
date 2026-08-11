// ============================================================
//  SALVEX DOCTOR PORTAL — dashboard.js  (v6 FULLY WORKING)
// ============================================================

let currentApptId   = null;
let currentApptData = null;

// ============================================================
// 1. SECTION NAVIGATION
// ============================================================
function showSection(name) {
    // Hide all panels
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    // Remove active from all nav items
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    // Show target panel
    const panel = document.getElementById('section-' + name);
    if (panel) panel.classList.add('active');

    // Highlight nav item with matching data-section
    const navEl = document.querySelector('.nav-item[data-section="' + name + '"]');
    if (navEl) navEl.classList.add('active');

    // Build schedule when that section opens
    if (name === 'schedule') buildSchedule();

    // Scroll main to top
    const main = document.querySelector('.main-content');
    if (main) main.scrollTo({ top: 0, behavior: 'smooth' });

    // Close dropdowns
    closeAllDropdowns();
    return false;
}

// ============================================================
// 2. NAVIGATION HELPERS
// ============================================================
function goToAppointments() {
    showSection('appointments');
    // Reset filter to All
    setTimeout(function() { filterAppts('all', null); }, 80);
}

function statCardClick(section, filter) {
    showSection(section);
    if (section === 'appointments' && filter && filter !== 'all') {
        setTimeout(function() { filterAppts(filter, null); }, 120);
    }
}

// ============================================================
// 3. SIDEBAR TOGGLE
// ============================================================
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var main    = document.querySelector('.main-content');
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        if (main) main.classList.toggle('expanded');
    }
}

// ============================================================
// 4. DROPDOWNS
// ============================================================
function closeAllDropdowns() {
    var np = document.getElementById('notifPanel');
    var pd = document.getElementById('profileDrop');
    if (np) np.classList.remove('open');
    if (pd) pd.classList.remove('open');
}

function toggleNotifPanel() {
    var np = document.getElementById('notifPanel');
    var pd = document.getElementById('profileDrop');
    np.classList.toggle('open');
    if (pd) pd.classList.remove('open');
}

function toggleProfileDrop() {
    var pd = document.getElementById('profileDrop');
    var np = document.getElementById('notifPanel');
    pd.classList.toggle('open');
    if (np) np.classList.remove('open');
}

function clearNotifs() {
    var list  = document.getElementById('notifList');
    var badge = document.getElementById('notifBadge');
    if (list) list.innerHTML = '<div style="padding:30px;text-align:center;color:#94a3b8;font-size:13px;"><i class="fa-solid fa-bell-slash" style="font-size:28px;display:block;margin-bottom:8px;"></i>No notifications</div>';
    if (badge) badge.style.display = 'none';
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notif-wrap'))   { var np = document.getElementById('notifPanel');  if (np) np.classList.remove('open'); }
    if (!e.target.closest('.profile-wrap')) { var pd = document.getElementById('profileDrop'); if (pd) pd.classList.remove('open'); }
});

// ============================================================
// 5. DARK MODE
// ============================================================
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    var isDark = document.body.classList.contains('dark-mode');
    document.cookie = 'darkMode=' + isDark + ';path=/;max-age=31536000';
    var icon = document.getElementById('darkModeIcon');
    if (icon) icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}

// ============================================================
// 6. DOCTOR STATUS UPDATE
// ============================================================
function updateDoctorStatus(newStatus) {
    var fd = new FormData();
    fd.append('action', 'update_doctor_status');
    fd.append('new_status', newStatus);

    fetch('update_status.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                showToast('Status updated to ' + newStatus, 'success');
            } else {
                showToast('Status updated to ' + newStatus + ' (demo)', 'info');
            }
            applyDoctorStatusDOM(newStatus);
        })
        .catch(function() {
            showToast('Status set to ' + newStatus, 'info');
            applyDoctorStatusDOM(newStatus);
        });
}

function applyDoctorStatusDOM(newStatus) {
    var el  = document.querySelector('.pd-val[data-field="status"]');
    var sel = document.getElementById('statusSelect');
    if (el)  { el.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1); el.className = 'pd-val status-text-' + newStatus; }
    if (sel) sel.value = newStatus;
}

// ============================================================
// 7. APPOINTMENT STATUS UPDATE (Confirm / Complete / Cancel)
// ============================================================
function updateApptStatus(id, newStatus) {
    if (!id) { showToast('No appointment selected', 'error'); return; }

    var fd = new FormData();
    fd.append('action', 'update_appointment');
    fd.append('appointment_id', id);
    fd.append('new_status', newStatus);

    fetch('update_status.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                showToast('Appointment marked as ' + newStatus + '!', 'success');
            } else {
                showToast('Marked as ' + newStatus + ' (demo)', 'info');
            }
            applyApptStatusDOM(id, newStatus);
            closePatientModal();
        })
        .catch(function() {
            showToast('Marked as ' + newStatus + ' (demo)', 'info');
            applyApptStatusDOM(id, newStatus);
            closePatientModal();
        });
}

function applyApptStatusDOM(id, newStatus) {
    // Update status badges in overview card and appointments card
    var selectors = [
        '#appt-' + id + ' .appt-status-badge',
        '#full-appt-' + id + ' .appt-status-badge'
    ];
    selectors.forEach(function(sel) {
        document.querySelectorAll(sel).forEach(function(b) {
            b.textContent = newStatus;
            b.className   = 'appt-status-badge status-' + newStatus.toLowerCase();
        });
    });

    // Remove action buttons for terminal states
    if (newStatus === 'Completed' || newStatus === 'Cancelled') {
        var toRemove = [
            '#appt-' + id + ' .btn-complete',
            '#appt-' + id + ' .btn-cancel-appt',
            '#appt-' + id + ' .btn-confirm-appt',
            '#full-appt-' + id + ' .btn-complete',
            '#full-appt-' + id + ' .btn-cancel-appt',
            '#full-appt-' + id + ' .btn-confirm-appt'
        ];
        toRemove.forEach(function(s) {
            document.querySelectorAll(s).forEach(function(b) { b.remove(); });
        });
    }

    // If confirmed — remove only confirm button
    if (newStatus === 'Confirmed') {
        var confSels = ['#appt-' + id + ' .btn-confirm-appt', '#full-appt-' + id + ' .btn-confirm-appt'];
        confSels.forEach(function(s) {
            document.querySelectorAll(s).forEach(function(b) { b.remove(); });
        });
    }

    // Update data-status for filter
    var fc = document.getElementById('full-appt-' + id);
    var oc = document.getElementById('appt-' + id);
    if (fc) fc.dataset.status = newStatus.toLowerCase();
    if (oc) oc.dataset.status = newStatus.toLowerCase();
}

// ============================================================
// 8. PATIENT DETAIL MODAL
// ============================================================
function openPatientModal(appt) {
    currentApptId   = appt.id;
    currentApptData = appt;

    var nameStr = appt.patient_name || 'Unknown';
    var init    = nameStr.split(' ').filter(Boolean).map(function(p) { return p[0].toUpperCase(); }).join('').slice(0, 2);

    document.getElementById('modalAvatar').textContent      = init;
    document.getElementById('modalPatientName').textContent = nameStr;
    document.getElementById('modalPatientMeta').textContent = (appt.specialty || '-') + '  |  ' + (appt.appointment_time || '-');
    document.getElementById('modalDate').textContent        = formatDate(appt.appointment_date);
    document.getElementById('modalTime').textContent        = appt.appointment_time || '-';
    document.getElementById('modalAge').textContent         = appt.patient_age ? appt.patient_age + ' yrs' : '-';
    document.getElementById('modalHospital').textContent    = appt.hospital_name || '-';
    document.getElementById('modalSpecialty').textContent   = appt.specialty || '-';
    document.getElementById('modalNotes').value             = appt.notes || '';
    var consultationLink = document.getElementById('modalConsultationLink');
    if (consultationLink) consultationLink.href = 'consultation.php?appointment_id=' + appt.id;

    var statusEl = document.getElementById('modalStatus');
    statusEl.textContent = appt.status || '-';
    statusEl.className   = 'modal-val appt-status-badge status-' + (appt.status || '').toLowerCase();

    var actionRow   = document.getElementById('modalActionRow');
    var confirmBtn  = document.querySelector('.btn-modal-confirm');
    var completeBtn = document.querySelector('.btn-modal-complete');
    var cancelBtn   = document.querySelector('.btn-modal-cancel');

    if (appt.status === 'Completed' || appt.status === 'Cancelled') {
        if (actionRow) actionRow.style.display = 'none';
    } else {
        if (actionRow)   actionRow.style.display   = 'flex';
        if (confirmBtn)  confirmBtn.style.display   = (appt.status === 'Pending') ? 'inline-flex' : 'none';
        if (completeBtn) completeBtn.style.display  = 'inline-flex';
        if (cancelBtn)   cancelBtn.style.display    = 'inline-flex';
    }

    document.getElementById('patientModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closePatientModal(e) {
    // If triggered by overlay click, only close if user clicked the overlay itself
    if (e && e.target !== document.getElementById('patientModal')) return;
    document.getElementById('patientModal').classList.remove('open');
    document.body.style.overflow = '';
    currentApptId   = null;
    currentApptData = null;
}

// ============================================================
// 9. SAVE NOTES
// ============================================================
function saveNotes() {
    if (!currentApptId) { showToast('No appointment selected', 'error'); return; }
    var notes = document.getElementById('modalNotes').value;
    var fd    = new FormData();
    fd.append('action', 'save_notes');
    fd.append('appointment_id', currentApptId);
    fd.append('notes', notes);

    fetch('update_status.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            showToast(data.status === 'success' ? 'Notes saved!' : 'Could not save notes', data.status === 'success' ? 'success' : 'error');
        })
        .catch(function() { showToast('Notes saved (demo)!', 'info'); });
}

// ============================================================
// 10. APPOINTMENT FILTER TABS
// ============================================================
function filterAppts(status, btn) {
    // Update active tab styling
    document.querySelectorAll('.filter-tab').forEach(function(t) { t.classList.remove('active'); });

    if (btn && btn.classList && btn.classList.contains('filter-tab')) {
        btn.classList.add('active');
    } else {
        // Find tab by onclick text match
        var matched = false;
        document.querySelectorAll('.filter-tab').forEach(function(t) {
            if ((t.getAttribute('onclick') || '').indexOf("'" + status + "'") !== -1) {
                t.classList.add('active');
                matched = true;
            }
        });
        // Fallback to first tab (All)
        if (!matched) {
            var first = document.querySelector('.filter-tab');
            if (first) first.classList.add('active');
        }
    }

    // Show/hide appointment cards
    var visibleCount = 0;
    document.querySelectorAll('.appt-card-full').forEach(function(card) {
        var match = (status === 'all') || (card.dataset.status === status.toLowerCase());
        card.style.display = match ? 'flex' : 'none';
        if (match) visibleCount++;
    });

    var emptyEl = document.getElementById('apptEmptyFiltered');
    if (emptyEl) emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
}

// ============================================================
// 11. MY SCHEDULE — Build time slots
// ============================================================
function buildSchedule() {
    var container   = document.getElementById('scheduleGrid');
    if (!container) return;

    var rawTime     = (window._docTime || '09:00 AM - 05:00 PM').trim();
    var bookedSlots = window._todayAppts || [];

    function toMins(ts) {
        if (!ts) return 0;
        ts = ts.trim().replace(/\s+/g, ' ');
        var match = ts.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (!match) return 0;
        var h   = parseInt(match[1], 10);
        var m   = parseInt(match[2], 10);
        var mod = match[3].toUpperCase();
        if (mod === 'PM' && h !== 12) h += 12;
        if (mod === 'AM' && h === 12) h  = 0;
        return h * 60 + m;
    }

    function toLabel(totalMins) {
        var h  = Math.floor(totalMins / 60);
        var m  = totalMins % 60;
        var ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return (h < 10 ? '0' + h : h) + ':' + (m < 10 ? '0' + m : m) + ' ' + ap;
    }

    var dashIdx = rawTime.indexOf(' - ');
    if (dashIdx === -1) {
        container.innerHTML = '<p style="color:#94a3b8;padding:30px;text-align:center;grid-column:1/-1;">Schedule format not recognised.</p>';
        return;
    }
    var startMins = toMins(rawTime.slice(0, dashIdx).trim());
    var endMins   = toMins(rawTime.slice(dashIdx + 3).trim());

    if (endMins <= startMins) {
        container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;"><i class="fa-solid fa-triangle-exclamation" style="font-size:32px;display:block;margin-bottom:12px;color:#f59e0b;"></i><strong>Schedule time error</strong></div>';
        return;
    }

    var now     = new Date();
    var curMins = now.getHours() * 60 + now.getMinutes();

    var html = '';
    for (var t = startMins; t <= endMins; t += 30) {
        var label     = toLabel(t);
        var isBooked  = bookedSlots.some(function(s) { return normaliseTime(s) === normaliseTime(label); });
        var isPast    = t < curMins;
        var cls       = isPast ? 'slot-past' : (isBooked ? 'slot-booked' : 'slot-available');
        var statusTxt = isPast ? 'Past' : (isBooked ? 'Booked' : 'Free');
        var icon      = isPast ? '🕐' : (isBooked ? '📅' : '✅');
        var click     = isBooked ? 'onclick="goToBookedSlot(\'' + label + '\')" role="button"' : '';
        html += '<div class="slot-block ' + cls + '" ' + click + ' title="' + statusTxt + ' — ' + label + '"><span class="slot-icon">' + icon + '</span>' + label + '<small>' + statusTxt + '</small></div>';
    }

    container.innerHTML = html || '<p style="color:#94a3b8;padding:30px;text-align:center;grid-column:1/-1;">No time slots to display.</p>';
}

function normaliseTime(ts) {
    if (!ts) return '';
    return ts.trim().replace(/\s+/g, ' ').replace(/^0/, '');
}

function goToBookedSlot(time) {
    showSection('appointments');
    setTimeout(function() {
        filterAppts('all', null);
        setTimeout(function() {
            document.querySelectorAll('.appt-card-full').forEach(function(card) {
                if (card.textContent.indexOf(time) !== -1 || card.textContent.indexOf(normaliseTime(time)) !== -1) {
                    card.style.outline    = '2.5px solid #0080FF';
                    card.style.background = '#eff6ff';
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(function() { card.style.outline = ''; card.style.background = ''; }, 2800);
                }
            });
        }, 80);
    }, 150);
}

// ============================================================
// 12. TOAST NOTIFICATIONS
// ============================================================
function showToast(msg, type) {
    type = type || 'success';
    var wrap = document.getElementById('doctorToast');
    if (!wrap) return;

    var icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info', warning: 'fa-triangle-exclamation' };
    var div   = document.createElement('div');
    div.className = 'toast-msg toast-' + type;
    div.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i><span>' + msg + '</span><button class="toast-close-btn" onclick="this.closest(\'.toast-msg\').remove()">&#x2715;</button>';
    wrap.appendChild(div);

    setTimeout(function() {
        if (!div.parentElement) return;
        div.style.opacity    = '0';
        div.style.transform  = 'translateX(120%)';
        div.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        setTimeout(function() { if (div.parentElement) div.remove(); }, 420);
    }, 3500);
}

// ============================================================
// 13. HELPERS
// ============================================================
function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d      = new Date(dateStr + 'T00:00:00');
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    return days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
}

// ============================================================
// 14. DOMContentLoaded — INIT
// ============================================================
document.addEventListener('DOMContentLoaded', function() {

    // Set dark mode icon
    if (document.body.classList.contains('dark-mode')) {
        var icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = 'fa-solid fa-sun';
    }

    // Inject data-section on nav items (used by showSection)
    document.querySelectorAll('.nav-item').forEach(function(n) {
        var oc    = n.getAttribute('onclick') || '';
        var match = oc.match(/'([^']+)'/);
        if (match) n.dataset.section = match[1];
    });

    // Animate stat counters
    document.querySelectorAll('.stat-num').forEach(function(el) {
        var target = parseInt(el.textContent.replace(/\D/g, ''), 10);
        if (!isNaN(target) && target > 0) {
            var cur  = 0;
            var step = Math.max(1, Math.ceil(target / 40));
            el.textContent = '0';
            var timer = setInterval(function() {
                cur = Math.min(cur + step, target);
                el.textContent = cur;
                if (cur >= target) clearInterval(timer);
            }, 22);
        }
    });

    // Ripple on stat cards
    document.querySelectorAll('.stat-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            var ripple = document.createElement('span');
            var rect   = card.getBoundingClientRect();
            ripple.style.cssText = 'position:absolute;border-radius:50%;pointer-events:none;z-index:0;width:60px;height:60px;left:' + (e.clientX - rect.left - 30) + 'px;top:' + (e.clientY - rect.top - 30) + 'px;background:rgba(0,128,255,0.14);transform:scale(0);animation:_ripple 0.55s ease-out forwards;';
            card.style.position = 'relative';
            card.style.overflow = 'hidden';
            card.appendChild(ripple);
            setTimeout(function() { ripple.remove(); }, 600);
        });
    });

    // Inject helper CSS
    if (!document.getElementById('_salvexHelperStyle')) {
        var s = document.createElement('style');
        s.id = '_salvexHelperStyle';
        s.textContent = '@keyframes _ripple{to{transform:scale(5);opacity:0}}.stat-card{cursor:pointer}.stat-arrow{transition:all 0.2s ease}.stat-card:hover .stat-arrow{color:var(--primary)!important;transform:translateX(3px)}.slot-booked{cursor:pointer}.slot-booked:hover{filter:brightness(0.93)}.toast-close-btn{background:none;border:none;color:#fff;cursor:pointer;font-size:13px;margin-left:auto;padding:0 0 0 10px;opacity:0.8}.toast-close-btn:hover{opacity:1}';
        document.head.appendChild(s);
    }
});
