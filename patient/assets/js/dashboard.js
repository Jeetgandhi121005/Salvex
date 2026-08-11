// ============================================================
//  SALVEX HMS — dashboard.js  v2.2
//  Fix: All 90 doctor fees, Patient name/age from inputs
// ============================================================

// ---- Per-Doctor Fees (specialty + hospital + experience based) ----//
// Example of how to use it in your booking summary function:
function updateSummary(doctorValue) {
    const consultFee = getDoctorFee(doctorValue);
    const total = consultFee + platformFee;
    
    document.getElementById('sum-consult-fee').textContent = '₹' + consultFee;
    document.getElementById('sum-total').textContent = '₹' + total;
}
const FAMILY_API_URL = 'family_member_action.php';
const BOOKED_SLOTS_API_URL = 'get_booked_slots.php';
let editingFamilyMemberId = null;
let familyMembersCache = [];
let currentDoctorSchedule = '';
let currentDoctorId = '';

function getDoctorFee(doctorValue) {
    if (doctorValue && typeof doctorValue === 'object') {
        const directFee = Number(doctorValue.consultation_fee || 0);
        if (directFee > 0) return directFee;
        const mappedFee = doctorFees[doctorValue.name];
        if (mappedFee) return mappedFee;
    }

    return doctorFees[doctorValue] || 800;
}

// ---- Format Date ----
function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d      = new Date(dateStr);
    const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

// ---- Booking Reference ----
function generateRef() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let ref = 'SLV-';
    for (let i = 0; i < 6; i++) ref += chars.charAt(Math.floor(Math.random() * chars.length));
    return ref;
}

// ---- Get patient info from saved family profiles ----
function getSelectedFamilyMember() {
    const el = document.getElementById('patientProfileSelect');
    if (!el || !el.value) return null;
    return familyMembersCache.find(member => String(member.id) === String(el.value)) || null;
}

function getPatientName() {
    const member = getSelectedFamilyMember();
    return member ? (member.member_name || '').trim() : '';
}

function getPatientAge() {
    const member = getSelectedFamilyMember();
    return member ? String(member.member_age || '').trim() : '';
}

function populatePatientProfileSelect(members) {
    const select = document.getElementById('patientProfileSelect');
    const hint = document.getElementById('patientProfileHint');
    if (!select) return;

    familyMembersCache = Array.isArray(members) ? members : [];
    select.innerHTML = '';

    if (familyMembersCache.length === 0) {
        select.innerHTML = '<option value="">No saved profiles found. Please add one in Manage Family.</option>';
        select.disabled = true;
        if (hint) hint.textContent = 'Add a family member profile first, then come back here to book the appointment.';
        updateSelectedPatientMeta();
        return;
    }

    select.disabled = false;
    select.innerHTML = '<option value="">Select a saved family profile</option>';

    familyMembersCache.forEach(member => {
        const option = document.createElement('option');
        option.value = member.id;
        option.textContent = `${member.member_name} (${member.relation}, ${member.member_age} yrs)`;
        select.appendChild(option);
    });

    if (hint) hint.textContent = 'Choose a saved family member profile to continue.';
    if (!select.value && familyMembersCache.length === 1) {
        select.value = String(familyMembersCache[0].id);
    }

    updateSelectedPatientMeta();
}

function updateSelectedPatientMeta() {
    const member = getSelectedFamilyMember();
    const metaWrap = document.getElementById('selectedPatientMeta');
    const relationEl = document.getElementById('selectedPatientRelation');
    const ageEl = document.getElementById('selectedPatientAge');
    const dobEl = document.getElementById('selectedPatientDob');

    if (!metaWrap || !relationEl || !ageEl || !dobEl) return;

    if (!member) {
        metaWrap.style.display = 'none';
        relationEl.textContent = 'Relation: —';
        ageEl.textContent = 'Age: —';
        dobEl.textContent = 'DOB: —';
        return;
    }

    relationEl.textContent = `Relation: ${member.relation || '—'}`;
    ageEl.textContent = `Age: ${member.member_age || '—'} Years`;
    dobEl.textContent = `DOB: ${member.dob || '—'}`;
    metaWrap.style.display = 'block';
}

// ============================================================
//  DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    // Doctor card
    const storedData  = localStorage.getItem('selectedDoctor');
    const displayArea = document.getElementById('displayDoctorCard');
        if (displayArea) {
            if (storedData) {
                const doc = JSON.parse(storedData);
                currentDoctorSchedule = doc.time || '';
                currentDoctorId = doc.id || '';
                const fee = getDoctorFee(doc);

            // Specialty se icon map
            const specIcons = {
                'Cardiology':'fa-heart','Neurology':'fa-brain','Orthopedics':'fa-bone',
                'Dermatology':'fa-hand-dots','Pediatrics':'fa-baby','Oncology':'fa-ribbon',
                'Gastroenterology':'fa-bacteria','Ophthalmology':'fa-eye','Nephrology':'fa-microscope',
                'Pulmonology':'fa-lungs','Endocrinology':'fa-droplet','Psychiatry':'fa-head-side-virus',
                'ENT':'fa-ear-listen','Urology':'fa-toilet-paper','Dentistry':'fa-tooth',
            };
            const specColors = {
                'Cardiology':    '#ef4444',
                'Neurology':     '#7c3aed',
                'Orthopedics':   '#f97316',
                'Dermatology':   '#ec4899',
                'Pediatrics':    '#06b6d4',
                'Oncology':      '#f43f5e',
                'Gastroenterology': '#10b981',
                'Ophthalmology': '#3b82f6',
                'Nephrology':    '#6366f1',
                'Pulmonology':   '#0ea5e9',
                'Endocrinology': '#f59e0b',
                'Psychiatry':    '#a855f7',
                'ENT':           '#14b8a6',
                'Urology':       '#64748b',
                'Dentistry':     '#22c55e',
            };
            const icon  = specIcons[doc.specialty]  || 'fa-stethoscope';
            const color = specColors[doc.specialty] || '#2563eb';
            const initials = doc.name.replace('Dr. ','').split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();

            displayArea.innerHTML = `
                <div class="doctor-card-new">
                    <div class="doctor-card-left">
                        <div class="doctor-avatar-wrap" style="--spec-color:${color};">
                            <span class="doctor-initials">${initials}</span>
                            <div class="doctor-spec-icon">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                        </div>
                        <div class="doctor-card-info">
                            <span class="doctor-card-spec-badge" style="background:${color}18;color:${color};">
                                <i class="fa-solid ${icon}" style="font-size:11px;"></i>
                                ${doc.specialty}
                            </span>
                            <h2 class="doctor-card-name">${doc.name}</h2>
                            <p class="doctor-card-hospital">
                                <i class="fa-solid fa-location-dot"></i> ${doc.hospital}
                            </p>
                        </div>
                    </div>
                    <div class="doctor-card-right">
                        <div class="doctor-chip">
                            <div class="chip-icon" style="background:${color}15;">
                                <i class="fa-solid fa-briefcase-medical" style="color:${color};"></i>
                            </div>
                            <div>
                                <span class="chip-label">Experience</span>
                                <span class="chip-val">${doc.exp}</span>
                            </div>
                        </div>
                        <div class="doctor-chip">
                            <div class="chip-icon" style="background:#2563eb15;">
                                <i class="fa-regular fa-clock" style="color:#2563eb;"></i>
                            </div>
                            <div>
                                <span class="chip-label">Available</span>
                                <span class="chip-val">${doc.time}</span>
                            </div>
                        </div>
                        <div class="doctor-chip">
                            <div class="chip-icon" style="background:#22c55e15;">
                                <i class="fa-solid fa-indian-rupee-sign" style="color:#22c55e;"></i>
                            </div>
                            <div>
                                <span class="chip-label">Consult Fee</span>
                                <span class="chip-val">₹${fee.toLocaleString('en-IN')}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            generateDynamicSlots(doc.time);
        } else {
            displayArea.innerHTML = `
                <div class="no-doctor-alert">
                    <div class="alert-icon">
                        <i class="fa-solid fa-user-doctor"></i>
                        <div class="icon-pulse"></div>
                    </div>
                    <div class="alert-content">
                        <h3>No Doctor Selected</h3>
                        <p>Please select a specialist to continue with your booking.</p>
                        <a href="index.php" class="btn-return">
                            <i class="fa-solid fa-magnifying-glass"></i> Find a Doctor
                        </a>
                    </div>
                </div>`;
        }
    }

    buildCalendar('dateWrapper', 'selectedAppointmentDate');
    refreshBookingSlots();
    loadFamilyMembers();

    const addFamilyForm = document.getElementById('addFamilyForm');
    if (addFamilyForm) {
        addFamilyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitFamilyMember(this);
        });
    }

    const patientProfileSelect = document.getElementById('patientProfileSelect');
    if (patientProfileSelect) {
        patientProfileSelect.addEventListener('change', updateSelectedPatientMeta);
    }

    document.querySelectorAll('.dob-field').forEach((field, index, fields) => {
        field.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length >= this.getAttribute('maxlength') && index < fields.length - 1)
                fields[index + 1].focus();
        });
        field.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0)
                fields[index - 1].focus();
        });
    });
});

// ============================================================
//  CALENDAR
// ============================================================
function buildCalendar(wrapperId, hiddenId) {
    const wrapper = document.getElementById(wrapperId);
    const hidden  = document.getElementById(hiddenId);
    if (!wrapper) return;

    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    for (let i = 0; i < 14; i++) {
        const d   = new Date();
        d.setDate(d.getDate() + i);
        const val = d.toISOString().split('T')[0];

        const card = document.createElement('div');
        card.className = `date-card ${i === 0 ? 'active' : ''}`;
        card.style.minWidth   = '80px';
        card.style.flexShrink = '0';
        if (i === 0 && hidden) hidden.value = val;

        card.innerHTML = `
            <span class="day">${days[d.getDay()]}</span>
            <span class="date">${d.getDate()}</span>
            <span class="month">${months[d.getMonth()]}</span>`;

        card.onclick = () => {
            wrapper.querySelectorAll('.date-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            if (hidden) hidden.value = val;
            refreshBookingSlots();
        };
        wrapper.appendChild(card);
    }

    refreshBookingSlots();
}

function normalizeTimeLabel(value) {
    return String(value || '')
        .trim()
        .replace(/\s+/g, ' ')
        .toUpperCase()
        .replace(/^0(\d:\d{2}\s*[AP]M)$/i, '$1');
}

async function fetchBookedSlots(doctorId, date) {
    if (!doctorId || !date) return [];

    try {
        const response = await fetch(`${BOOKED_SLOTS_API_URL}?doctor_id=${encodeURIComponent(doctorId)}&date=${encodeURIComponent(date)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        if (data.status !== 'success' || !Array.isArray(data.slots)) {
            return [];
        }

        return data.slots.map(normalizeTimeLabel);
    } catch (error) {
        return [];
    }
}

async function refreshBookingSlots() {
    if (!currentDoctorSchedule) return;
    await generateDynamicSlots(currentDoctorSchedule);
}

// ============================================================
//  SLOTS
// ============================================================
async function generateDynamicSlots(timeRange, containerId = 'timeSlotsContainer') {
    const container = document.getElementById(containerId);
    if (!container) return;

    const parts = timeRange.split(' - ');
    if (parts.length < 2) return;

    function toMins(ts) {
        const [time, mod] = ts.trim().split(' ');
        let [h, m] = time.split(':');
        if (h === '12') h = '00';
        if (mod === 'PM') h = parseInt(h, 10) + 12;
        return parseInt(h, 10) * 60 + parseInt(m, 10);
    }
    function toTime(mins) {
        let h = Math.floor(mins / 60), m = mins % 60;
        const ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h < 10 ? '0'+h : h}:${m < 10 ? '0'+m : m} ${ap}`;
    }

    const start    = toMins(parts[0]);
    const end      = toMins(parts[1]);
    const now      = new Date();
    const curMins  = now.getHours() * 60 + now.getMinutes();
    const todayStr = now.toISOString().split('T')[0];
    const selDate  = document.getElementById('selectedAppointmentDate')?.value || '';
    const bookedSlots = await fetchBookedSlots(currentDoctorId, selDate);

    let html = '';
    for (let t = start; t <= end; t += 15) {
        const label  = toTime(t);
        const isPast = (selDate === todayStr && t < curMins + 15);
        const isBooked = bookedSlots.includes(normalizeTimeLabel(label));

        if (isPast || isBooked) {
            continue;
        }

        html += `<div class="slot-item" onclick="selectSlot(this)" style="cursor:pointer;border:1px solid #e2e8f0;border-radius:8px;padding:12px 5px;text-align:center;transition:0.3s;">${label}</div>`;
    }

    const temp      = document.createElement('div');
    temp.innerHTML  = html;
    const all       = Array.from(temp.childNodes);
    const first10   = all.slice(0, 10);
    const rest      = all.slice(10);

    const grid = document.createElement('div');
    grid.style.cssText = 'display:grid;grid-template-columns:repeat(5,1fr);gap:12px;width:100%;margin-top:20px;';
    first10.forEach(n => grid.appendChild(n.cloneNode(true)));

    container.innerHTML = '';

    if (all.length === 0) {
        container.innerHTML = `<div style="grid-column:1/-1;padding:18px;border:1px dashed #cbd5e1;border-radius:12px;text-align:center;color:#64748b;background:#f8fafc;">No slots available for this date. Please choose another day.</div>`;
        return;
    }

    container.appendChild(grid);

    if (rest.length > 0) {
        const moreDiv = document.createElement('div');
        moreDiv.style.cssText = 'display:grid;grid-template-columns:repeat(5,1fr);gap:12px;width:100%;max-height:0;overflow:hidden;transition:all 0.5s ease;opacity:0;';
        rest.forEach(n => moreDiv.appendChild(n.cloneNode(true)));

        const btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = 'view-more-btn';
        btn.style.cssText = 'width:100%;margin-top:15px;';
        btn.innerHTML = `<span>View More Slots</span><i class="fa-solid fa-chevron-down" style="margin-left:8px;transition:0.4s;"></i>`;
        btn.onclick = function() {
            const open = moreDiv.style.maxHeight !== '0px' && moreDiv.style.maxHeight !== '';
            moreDiv.style.maxHeight  = open ? '0' : '2000px';
            moreDiv.style.opacity    = open ? '0' : '1';
            moreDiv.style.marginTop  = open ? '0' : '12px';
            btn.querySelector('span').innerText = open ? 'View More Slots' : 'Show Less';
            btn.querySelector('i').style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
        };
        container.appendChild(moreDiv);
        container.appendChild(btn);
    }
}

function selectSlot(el) {
    document.querySelectorAll('.slot-item').forEach(s => s.classList.remove('active'));
    el.classList.add('active');
}

// ============================================================
//  BOOKING SUMMARY
// ============================================================
function handleBooking() {
    const doc  = JSON.parse(localStorage.getItem('selectedDoctor') || 'null');
    const date = document.getElementById('selectedAppointmentDate')?.value;
    const slot = document.querySelector('.slot-item.active');

    if (!date) { showAlert('Select Date', 'Please select a date from the calendar.', 'warning'); return; }
    if (!slot)  { showAlert('Select Time Slot', 'Please choose a time slot to continue.', 'warning'); return; }
    if (!doc)   { showAlert('No Doctor Selected', 'Please go back to the home page and select a doctor first.', 'warning'); return; }

    const patientName = getPatientName();
    const patientAge  = getPatientAge();

    if (!patientName) { showAlert('Patient Profile Required', 'Please choose a saved family member profile to continue.', 'warning'); return; }
    if (!patientAge)  { showAlert('Patient Profile Incomplete', 'The selected family member does not have a valid age.', 'warning'); return; }

    const fee   = getDoctorFee(doc);
    const total = fee + PLATFORM_FEE;
    const patientDisplay = `${patientName}, ${patientAge} yrs`;

    document.getElementById('sum-doctor').textContent    = doc.name;
    document.getElementById('sum-specialty').textContent = doc.specialty;
    document.getElementById('sum-hospital').textContent  = doc.hospital;
    document.getElementById('sum-date').textContent      = formatDate(date);
    document.getElementById('sum-time').textContent      = slot.innerText;
    document.getElementById('sum-patient').textContent   = patientDisplay;
    document.getElementById('sum-consult-fee').textContent = '₹' + fee.toLocaleString('en-IN');
    document.getElementById('sum-total').textContent     = '₹' + total.toLocaleString('en-IN');

    document.getElementById('bookingSummaryModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSummaryModal() {
    document.getElementById('bookingSummaryModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ============================================================
//  SUBMIT & CONFIRM
// ============================================================
function submitBooking() {
    const doc  = JSON.parse(localStorage.getItem('selectedDoctor') || 'null');
    const date = document.getElementById('selectedAppointmentDate')?.value;
    const slot = document.querySelector('.slot-item.active');
    if (!doc || !date || !slot) return;

    const patientName = getPatientName();
    const patientAge  = getPatientAge();
    const fee         = getDoctorFee(doc);
    const total       = fee + PLATFORM_FEE;

    if (!patientName || !patientAge) {
        showAlert('Patient Profile Required', 'Please choose a saved family member profile before booking.', 'warning');
        return;
    }

    const fd = new FormData();
    fd.append('doctor_id',        doc.id || '');
    fd.append('doctor_name',      doc.name);
    fd.append('specialty',        doc.specialty);
    fd.append('hospital_name',    doc.hospital);
    fd.append('date',             date);
    fd.append('time',             slot.innerText);
    fd.append('patient_name',     patientName);
    fd.append('patient_age',      patientAge);
    fd.append('consultation_fee', fee);

    fetch('book_appointment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            closeSummaryModal();
            showConfirmedModal(doc, date, slot.innerText, total, patientName, patientAge, data.invoice_no || '');
        } else {
            showAlert('Booking Failed', data.message || 'Something went wrong. Please try again.', 'error');
        }
    })
    .catch(() => showAlert('Error', 'Something went wrong. Please try again.', 'error'));
}

function showConfirmedModal(doc, date, time, total, patientName, patientAge, invoiceNo) {
    const display = `${patientName}, ${patientAge} yrs`;
    document.getElementById('conf-ref').textContent       = invoiceNo || generateRef();
    document.getElementById('conf-doctor').textContent    = doc.name;
    document.getElementById('conf-specialty').textContent = doc.specialty;
    document.getElementById('conf-hospital').textContent  = doc.hospital;
    document.getElementById('conf-date').textContent      = formatDate(date);
    document.getElementById('conf-time').textContent      = time;
    document.getElementById('conf-patient').textContent   = display;
    document.getElementById('conf-total').textContent     = '₹' + total.toLocaleString('en-IN') + ' (incl. platform fee)';

    const phoneCard = document.getElementById('conf-phone-card');
    if (phoneCard) phoneCard.style.display = 'none';

    document.getElementById('bookingConfirmedModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function printSlip() {
    const modal = document.getElementById('bookingConfirmedModal');
    const inner = modal.querySelector('.confirmed-modal');
    const printWin = window.open('', '_blank', 'width=700,height=800');
    printWin.document.write(`
        <!DOCTYPE html><html><head>
        <title>Appointment Slip | Salvex</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Inter',sans-serif; background:#fff; padding:30px; color:#0f172a; }
            .confirmed-modal { max-width:600px; margin:0 auto; }
            .confirmed-check { width:60px;height:60px;background:#22c55e;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:#fff; }
            .confirmed-title { font-size:22px;font-weight:800;text-align:center;margin-bottom:8px; }
            .confirmed-sub { font-size:13px;color:#64748b;text-align:center;margin-bottom:16px;line-height:1.6; }
            .booking-ref-badge { display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px;padding:8px 18px;font-size:13px;color:#475569;margin-bottom:20px; }
            .booking-ref-badge strong { color:#2563eb;font-size:14px; }
            .confirmed-details-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px; }
            .conf-detail-card { display:flex;align-items:center;gap:10px;background:#f8fafc;border-radius:10px;padding:12px 14px;border:1px solid #f1f5f9; }
            .conf-detail-icon { width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0; }
            .conf-label { font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px;display:block; }
            .conf-val { font-size:13px;font-weight:600;color:#0f172a;display:block;margin-top:2px; }
            .confirmed-actions { display:none; }
            .salvex-print-footer { text-align:center;margin-top:20px;padding-top:16px;border-top:1px dashed #e2e8f0;font-size:11px;color:#94a3b8; }
            @media print { body { padding:20px; } }
        </style>
        </head><body>
        ${inner.outerHTML}
        <div class="salvex-print-footer">Salvex Health Management System &bull; support@salvex.com &bull; +91 79 1234 5678</div>
        </body></html>`);
    printWin.document.close();
    printWin.focus();
    setTimeout(() => { printWin.print(); printWin.close(); }, 600);
}
function goNewBooking()  { localStorage.removeItem('selectedDoctor'); window.location.href = 'index.php'; }
function goHome()        { localStorage.removeItem('selectedDoctor'); window.location.href = 'index.php'; }
function clearAndGoHome(){ localStorage.removeItem('selectedDoctor'); window.location.href = 'dashboard.php?view=appointments'; }

// ============================================================
//  CANCEL
// ============================================================
function confirmCancel(id) {
    document.getElementById('cancelAppointmentId').value = id;
    document.getElementById('cancelConfirmModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeCancelModal() {
    document.getElementById('cancelConfirmModal').style.display = 'none';
    document.body.style.overflow = '';
}
function proceedCancel() {
    const id = document.getElementById('cancelAppointmentId').value;
    const fd = new FormData();
    fd.append('action', 'cancel');
    fd.append('appointment_id', id);
    fetch('appointment_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        closeCancelModal();
        showToast(data.status === 'success' ? 'Appointment cancelled.' : (data.message || 'Could not cancel.'),
                  data.status === 'success' ? 'success' : 'error');
        if (data.status === 'success') setTimeout(() => location.reload(), 1500);
    })
    .catch(() => { closeCancelModal(); showToast('Something went wrong.', 'error'); });
}

// ============================================================
//  RESCHEDULE
// ============================================================
function openRescheduleModal(id, doctorName, currentDate, currentTime) {
    document.getElementById('rescheduleAppointmentId').value = id;
    document.getElementById('reschedule-doctor-name').textContent = doctorName;

    const wrap   = document.getElementById('rescheduleDateWrapper');
    const hidden = document.getElementById('rescheduleSelectedDate');
    while (wrap.children.length > 1) wrap.removeChild(wrap.lastChild);

    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const days   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    for (let i = 0; i < 14; i++) {
        const d   = new Date();
        d.setDate(d.getDate() + i);
        const val = d.toISOString().split('T')[0];
        const active = val === currentDate;

        const card = document.createElement('div');
        card.className    = `date-card ${active ? 'active' : ''}`;
        card.style.minWidth   = '80px';
        card.style.flexShrink = '0';
        if (active) hidden.value = val;

        card.innerHTML = `
            <span class="day">${days[d.getDay()]}</span>
            <span class="date">${d.getDate()}</span>
            <span class="month">${months[d.getMonth()]}</span>`;
        card.onclick = () => {
            wrap.querySelectorAll('.date-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            hidden.value = val;
        };
        wrap.appendChild(card);
    }

    buildRescheduleSlots(currentTime);
    document.getElementById('rescheduleModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function buildRescheduleSlots(activeTime) {
    const container = document.getElementById('rescheduleTimeSlots');
    container.innerHTML = '';
    for (let t = 9*60; t <= 17*60; t += 30) {
        let h = Math.floor(t / 60), m = t % 60;
        const ap = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const label = `${h < 10 ? '0'+h : h}:${m < 10 ? '0'+m : m} ${ap}`;
        const div = document.createElement('div');
        div.className = 'slot-item' + (label === activeTime ? ' active' : '');
        div.style.cssText = 'cursor:pointer;border:1px solid #e2e8f0;border-radius:8px;padding:10px 5px;text-align:center;transition:0.3s;font-size:13px;';
        div.textContent = label;
        div.onclick = function() {
            container.querySelectorAll('.slot-item').forEach(s => s.classList.remove('active'));
            div.classList.add('active');
        };
        container.appendChild(div);
    }
}

function closeRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
    document.body.style.overflow = '';
}

function submitReschedule() {
    const id   = document.getElementById('rescheduleAppointmentId').value;
    const date = document.getElementById('rescheduleSelectedDate').value;
    const slot = document.querySelector('#rescheduleTimeSlots .slot-item.active');

    if (!date) { showToast('Please select a new date.', 'error'); return; }
    if (!slot) { showToast('Please select a time slot.', 'error'); return; }

    const fd = new FormData();
    fd.append('action', 'reschedule');
    fd.append('appointment_id', id);
    fd.append('new_date', date);
    fd.append('new_time', slot.textContent);

    fetch('appointment_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        closeRescheduleModal();
        showToast(data.status === 'success' ? 'Rescheduled successfully!' : (data.message || 'Could not reschedule.'),
                  data.status === 'success' ? 'success' : 'error');
        if (data.status === 'success') setTimeout(() => location.reload(), 1500);
    })
    .catch(() => { closeRescheduleModal(); showToast('Something went wrong.', 'error'); });
}

// ============================================================
//  TOAST
// ============================================================
function showToast(msg, type = 'success') {
    const old = document.getElementById('salvex-toast');
    if (old) old.remove();
    const toast = document.createElement('div');
    toast.id = 'salvex-toast';
    toast.style.cssText = `position:fixed;bottom:30px;right:30px;background:${type==='success'?'#22c55e':'#ef4444'};color:#fff;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:600;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;animation:slideInToast 0.3s ease;`;
    toast.innerHTML = `<i class="fa-solid fa-${type==='success'?'circle-check':'circle-xmark'}"></i> ${msg}`;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity='0'; toast.style.transition='0.4s'; setTimeout(()=>toast.remove(),400); }, 3000);
}

// ============================================================
//  FAMILY
// ============================================================
function openAddMemberModal() { document.getElementById('addFamilyModal').style.display='flex'; }
function closeModal() {
    const modal = document.getElementById('addFamilyModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    resetFamilyForm();
}

function showFamilyModal() {
    const modal = document.getElementById('addFamilyModal');
    if (modal) modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function resetFamilyForm() {
    const form = document.getElementById('addFamilyForm');
    if (form) form.reset();

    editingFamilyMemberId = null;

    const memberIdField = document.getElementById('familyMemberId');
    if (memberIdField) memberIdField.value = '';

    const title = document.getElementById('familyModalTitle');
    if (title) title.textContent = 'Add Family Member';

    const submitBtn = document.getElementById('familySubmitBtn');
    if (submitBtn) {
        submitBtn.textContent = 'Save Profile';
        submitBtn.disabled = false;
    }
}

function openAddMemberModal() {
    resetFamilyForm();
    showFamilyModal();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}

function renderEmptyFamilyState() {
    const card = document.createElement('div');
    card.id = 'emptyState';
    card.className = 'empty-state';
    card.style.gridColumn = '1/-1';
    card.innerHTML = '<p>No family profiles added yet.</p>';
    return card;
}

function addFamilyCardToUI(member) {
    const list = document.getElementById('familyList');
    if (!list) return;

    const card = document.createElement('div');
    card.className = 'doctor-info-banner family-card-horizontal';
    card.style.cssText = 'margin-bottom:20px;display:flex;gap:20px;';
    card.dataset.memberId = member.id;
    card.dataset.memberName = member.member_name;
    card.dataset.relation = member.relation;
    card.dataset.memberAge = member.member_age;
    card.dataset.dob = member.dob || '';
    card.innerHTML = `
        <div class="card-section identity">
            <span class="relation-tag">${escapeHtml(member.relation)}</span>
            <h2 class="member-name">${escapeHtml(member.member_name)}</h2>
        </div>
        <div class="card-section details">
            <div class="detail-item"><i class="fa-solid fa-user-clock"></i><span>Age: <strong>${escapeHtml(member.member_age)} Years</strong></span></div>
            <div class="detail-item"><i class="fa-regular fa-calendar-days"></i><span>DOB: <strong>${escapeHtml(member.dob || '—')}</strong></span></div>
        </div>
        <div class="card-section actions" style="display:flex;gap:15px;align-items:center;justify-content:flex-end;margin-left:auto;padding-right:10px;">
            <button onclick="editFamilyMember(this)" style="display:flex;align-items:center;gap:8px;min-width:90px;height:40px;padding:0 15px;border-radius:8px;border:1px solid #d1d5db;background:#f3f4f6;cursor:pointer;">
                <i class="fa-solid fa-pen" style="font-size:14px;color:#4b5563;"></i>
                <span style="font-weight:600;color:#374151;font-size:14px;">Edit</span>
            </button>
            <button onclick="removeFamilyMember(this)" style="display:flex;align-items:center;gap:8px;min-width:105px;height:40px;padding:0 15px;border-radius:8px;border:1px solid #fee2e2;background:#fef2f2;cursor:pointer;">
                <i class="fa-solid fa-trash-can" style="font-size:14px;color:#ef4444;"></i>
                <span style="font-weight:600;color:#ef4444;font-size:14px;">Delete</span>
            </button>
        </div>`;
    list.appendChild(card);
}

function renderFamilyMembers(members) {
    const list = document.getElementById('familyList');
    populatePatientProfileSelect(members);
    if (!list) return;

    list.innerHTML = '';

    if (!Array.isArray(members) || members.length === 0) {
        list.appendChild(renderEmptyFamilyState());
        return;
    }

    members.forEach(addFamilyCardToUI);
}

function getFormattedDobFromForm() {
    const day = document.getElementById('dob-day').value.trim();
    const month = document.getElementById('dob-month').value.trim();
    const year = document.getElementById('dob-year').value.trim();
    const dob = `${day.padStart(2, '0')}/${month.padStart(2, '0')}/${year}`;
    return { day, month, year, dob };
}

function isValidDob(day, month, year) {
    if (!/^\d{1,2}$/.test(day) || !/^\d{1,2}$/.test(month) || !/^\d{4}$/.test(year)) return false;
    const d = parseInt(day, 10);
    const m = parseInt(month, 10);
    const y = parseInt(year, 10);
    if (y < 1900 || y > 2100) return false;
    const date = new Date(y, m - 1, d);
    return date.getFullYear() === y && date.getMonth() === m - 1 && date.getDate() === d;
}

async function submitFamilyMember(form) {
    const name = form.member_name.value.trim();
    const relation = form.relation.value.trim();
    const age = form.member_age.value.trim();
    const memberId = document.getElementById('familyMemberId').value.trim();
    const { day, month, year, dob } = getFormattedDobFromForm();

    if (!name || !relation || !age) {
        showToast('Please fill all required family member details.', 'error');
        return;
    }

    if (!/^\d+$/.test(age) || Number(age) < 1 || Number(age) > 120) {
        showToast('Please enter a valid age between 1 and 120.', 'error');
        return;
    }

    if (!isValidDob(day, month, year)) {
        showToast('Please enter a valid date of birth.', 'error');
        return;
    }

    const submitBtn = document.getElementById('familySubmitBtn');
    if (submitBtn) submitBtn.disabled = true;

    const fd = new FormData();
    fd.append('action', memberId ? 'update' : 'create');
    fd.append('member_id', memberId);
    fd.append('member_name', name);
    fd.append('relation', relation);
    fd.append('member_age', age);
    fd.append('dob', dob);

    try {
        const response = await fetch(FAMILY_API_URL, { method: 'POST', body: fd });
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Could not save family member.');
        }

        closeModal();
        await loadFamilyMembers();
        showToast(data.message || (memberId ? 'Profile updated successfully.' : 'Profile added successfully.'), 'success');
    } catch (error) {
        if (submitBtn) submitBtn.disabled = false;
        showToast(error.message || 'Something went wrong.', 'error');
    }
}

function editFamilyMember(btn) {
    const card = btn.closest('.family-card-horizontal');
    if (!card) return;

    const dobParts = String(card.dataset.dob || '').split('/');
    const form = document.getElementById('addFamilyForm');
    if (!form) return;

    editingFamilyMemberId = card.dataset.memberId || '';
    document.getElementById('familyMemberId').value = editingFamilyMemberId;
    form.member_name.value = card.dataset.memberName || '';
    form.relation.value = card.dataset.relation || '';
    form.member_age.value = card.dataset.memberAge || '';
    document.getElementById('dob-day').value = dobParts[0] || '';
    document.getElementById('dob-month').value = dobParts[1] || '';
    document.getElementById('dob-year').value = dobParts[2] || '';

    const title = document.getElementById('familyModalTitle');
    if (title) title.textContent = 'Edit Family Member';

    const submitBtn = document.getElementById('familySubmitBtn');
    if (submitBtn) submitBtn.textContent = 'Update Profile';

    showFamilyModal();
}

async function removeFamilyMember(btn) {
    const card = btn.closest('.family-card-horizontal');
    if (!card) return;

    const memberId = card.dataset.memberId;
    if (!memberId) {
        showToast('Could not identify this family member.', 'error');
        return;
    }

    if (!window.confirm('Delete this family member profile?')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('member_id', memberId);

    try {
        const response = await fetch(FAMILY_API_URL, { method: 'POST', body: fd });
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Could not delete family member.');
        }

        await loadFamilyMembers();
        showToast(data.message || 'Profile deleted successfully.', 'success');
    } catch (error) {
        showToast(error.message || 'Something went wrong.', 'error');
    }
}

function checkEmptyState() {
    const fl = document.getElementById('familyList');
    if (!fl) return;
    if (fl.getElementsByClassName('family-card-horizontal').length === 0) {
        fl.innerHTML = '';
        fl.appendChild(renderEmptyFamilyState());
    }
}

async function loadFamilyMembers() {
    const fl = document.getElementById('familyList');

    try {
        const response = await fetch(`${FAMILY_API_URL}?action=list`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Could not load family members.');
        }

        renderFamilyMembers(data.members || []);
    } catch (error) {
        familyMembersCache = [];
        populatePatientProfileSelect([]);
        if (fl) {
            fl.innerHTML = '';
            fl.appendChild(renderEmptyFamilyState());
        }
        showToast(error.message || 'Could not load family members.', 'error');
    }
}

const styleEl = document.createElement('style');
styleEl.textContent = `@keyframes slideInToast{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}`;
document.head.appendChild(styleEl);

// ============================================================
//  CUSTOM ALERT MODAL
// ============================================================
function showAlert(title, msg, type = 'info') {
    const modal    = document.getElementById('salvexAlertModal');
    const iconWrap = document.getElementById('alertIconWrap');
    const icon     = document.getElementById('alertIcon');
    const titleEl  = document.getElementById('alertTitle');
    const msgEl    = document.getElementById('alertMsg');

    const config = {
        info:    { bg:'#eff6ff', color:'#3b82f6', icon:'fa-circle-info' },
        warning: { bg:'#fff7ed', color:'#f97316', icon:'fa-triangle-exclamation' },
        error:   { bg:'#fee2e2', color:'#ef4444', icon:'fa-circle-xmark' },
        success: { bg:'#f0fdf4', color:'#22c55e', icon:'fa-circle-check' },
    };
    const c = config[type] || config.info;
    iconWrap.style.background = c.bg;
    icon.className = `fa-solid ${c.icon}`;
    icon.style.color = c.color;
    titleEl.textContent = title;
    msgEl.textContent   = msg;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAlert() {
    document.getElementById('salvexAlertModal').style.display = 'none';
    document.body.style.overflow = '';
}

// ============================================================
//  BILL SLIP MODAL
// ============================================================
function openBillModal(encoded) {
    const data = JSON.parse(atob(encoded));
    const statusColors = {
        'Pending':   { bg:'#fff7ed', color:'#c2410c' },
        'Confirmed': { bg:'#f0fdf4', color:'#166534' },
        'Cancelled': { bg:'#fef2f2', color:'#b91c1c' },
        'Paid':      { bg:'#f0fdf4', color:'#166534' },
        'Unpaid':    { bg:'#fff7ed', color:'#c2410c' },
    };
    const sc = statusColors[data.status] || statusColors['Pending'];

    document.getElementById('billSlipContent').innerHTML = `
        <div class="bill-slip-ref">
            <i class="fa-solid fa-hashtag"></i>
            Booking Ref: <strong>${data.ref}</strong>
        </div>
        <div class="bill-slip-grid">
            <div class="bill-slip-item">
                <span class="bill-slip-label">Doctor</span>
                <span class="bill-slip-val">${data.doctor}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Specialty</span>
                <span class="bill-slip-val">${data.specialty}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Hospital</span>
                <span class="bill-slip-val">${data.hospital}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Date</span>
                <span class="bill-slip-val">${data.date}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Time</span>
                <span class="bill-slip-val">${data.time}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Patient Name</span>
                <span class="bill-slip-val">${data.patient || '—'}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Patient Age</span>
                <span class="bill-slip-val">${data.age ? data.age + ' yrs' : '—'}</span>
            </div>
            <div class="bill-slip-item">
                <span class="bill-slip-label">Status</span>
                <span class="bill-slip-val">
                    <span style="background:${sc.bg};color:${sc.color};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">${data.status}</span>
                </span>
            </div>
        </div>
        <div class="bill-slip-fee-box">
            <div class="bill-slip-fee-row"><span>Consultation Fee</span><span>₹${data.consult.toLocaleString('en-IN')}</span></div>
            <div class="bill-slip-fee-row"><span>Platform Fee</span><span>₹${data.platform}</span></div>
            <div class="bill-slip-divider"></div>
            <div class="bill-slip-fee-row bill-slip-total"><span>Total Amount</span><span>₹${data.total.toLocaleString('en-IN')}</span></div>
        </div>
        <p class="bill-slip-note"><i class="fa-solid fa-circle-info"></i> This is a computer-generated bill. No signature required.</p>`;

    document.getElementById('billSlipModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeBillModal() {
    document.getElementById('billSlipModal').style.display = 'none';
    document.body.style.overflow = '';
}
function printBill() {
    const content = document.getElementById('billSlipContent').innerHTML;
    const header  = document.querySelector('.bill-slip-header');
    const logoSrc = header ? header.querySelector('img')?.src : '';
    const printWin = window.open('', '_blank', 'width=700,height=800');
    printWin.document.write(`
        <!DOCTYPE html><html><head>
        <title>Appointment Bill | Salvex</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family:'Inter',sans-serif; background:#fff; padding:30px; color:#0f172a; }
            .bill-wrap { max-width:520px; margin:0 auto; }
            .bill-header { display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;margin-bottom:16px; }
            .bill-header img { height:36px; }
            .bill-header h2 { font-size:18px;color:#0f172a;margin:0; }
            .bill-header p  { font-size:12px;color:#64748b;margin:0; }
            .bill-slip-ref { background:#f8fafc;border:1px solid #e2e8f0;border-radius:30px;padding:8px 18px;font-size:13px;color:#475569;display:inline-flex;align-items:center;gap:7px;margin-bottom:16px; }
            .bill-slip-ref strong { color:#2563eb;font-size:14px; }
            .bill-slip-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px; }
            .bill-slip-item { display:flex;flex-direction:column;gap:3px;background:#f8fafc;border-radius:10px;padding:12px 14px;border:1px solid #f1f5f9; }
            .bill-slip-label { font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.4px; }
            .bill-slip-val   { font-size:14px;font-weight:600;color:#0f172a; }
            .bill-slip-fee-box { background:#f8fafc;border-radius:12px;padding:16px 18px;margin-bottom:12px; }
            .bill-slip-fee-row { display:flex;justify-content:space-between;font-size:14px;color:#475569;font-weight:500;padding:4px 0; }
            .bill-slip-divider { border:none;border-top:1px dashed #e2e8f0;margin:8px 0; }
            .bill-slip-total { font-size:17px;font-weight:800;color:#0f172a; }
            .bill-slip-note { font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:6px;margin-bottom:16px; }
            .salvex-print-footer { text-align:center;padding-top:16px;border-top:1px dashed #e2e8f0;font-size:11px;color:#94a3b8; }
            @media print { body { padding:20px; } }
        </style>
        </head><body>
        <div class="bill-wrap">
            <div class="bill-header">
                <img src="${logoSrc}" alt="Salvex">
                <div><h2>Appointment Bill</h2><p>Salvex Health Management System</p></div>
            </div>
            ${content}
            <div class="salvex-print-footer">support@salvex.com &bull; +91 79 1234 5678 &bull; salvex.com</div>
        </div>
        </body></html>`);
    printWin.document.close();
    printWin.focus();
    setTimeout(() => { printWin.print(); printWin.close(); }, 600);
}
