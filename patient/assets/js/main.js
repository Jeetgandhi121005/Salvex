const overlay = document.getElementById('overlay');
const mainContent = document.getElementById('main-content');
const searchInput = document.getElementById('mainSearch');
const resultsList = document.getElementById('results-list');
const resultHeader = document.getElementById('result-header');

function normalizeCategory(value) {
    return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, ' ');
}

function openOverlay() {
    if (overlay) overlay.style.display = 'block';
    document.body.classList.add('no-scroll');
    if (mainContent) {
        mainContent.style.visibility = 'hidden';
        mainContent.style.height = '0';
    }
}

function closeOverlay() {
    if (overlay) overlay.style.display = 'none';

    if (searchInput) {
        searchInput.value = '';
        searchInput.setAttribute('value', '');
        searchInput.blur();
        setTimeout(() => {
            searchInput.value = '';
        }, 10);
    }

    if (mainContent) {
        mainContent.style.visibility = 'visible';
        mainContent.style.height = 'auto';
    }

    document.body.classList.remove('no-scroll');

    if (resultsList) resultsList.innerHTML = '';
    if (resultHeader) resultHeader.innerHTML = '';
}

function renderDoctorCard(doctor) {
    return `
        <div class="card doctor-card"
             style="border-left-color: var(--secondary)"
             data-doctor-id="${doctor.id || ''}"
             data-consultation-fee="${doctor.consultation_fee || 0}">
            <h3 class="doc-name">${doctor.name || ''}</h3>
            <p><strong class="doc-specialty">${doctor.profession || ''}</strong></p>
            <p><small class="doc-hospital">${doctor.hospital || ''}</small></p>
            <p>Exp: <span class="doc-exp">${doctor.exp || 'N/A'}</span> | Time: <span class="doc-time">${doctor.time || 'Not Available'}</span></p>
            <button class="btn-view" style="background: var(--secondary)">Book Now</button>
        </div>`;
}

function renderHospitalCard(hospital) {
    return `
        <div class="card">
            <h3>${hospital.name}</h3>
            <p><i class="fa-solid fa-location-dot"></i> ${hospital.location}</p>
            <p><strong>View Specialists</strong></p>
            <button class="btn-view" onclick="filterByHospital('${hospital.name.replace(/'/g, "\\'")}')">View Doctors</button>
        </div>`;
}

function renderDoctorResults(doctors, title, subtitle) {
    resultHeader.innerHTML = `<h1>${title}</h1><p>${subtitle}</p>`;

    if (doctors.length > 0) {
        resultsList.innerHTML = doctors.map(renderDoctorCard).join('');
    } else {
        resultsList.innerHTML = `
            <div class="card" style="max-width:420px;">
                <h3>No doctors found</h3>
                <p>No doctors are available right now for <strong>${title}</strong>.</p>
            </div>
        `;
    }

    openOverlay();
}

if (searchInput) {
    searchInput.addEventListener('keyup', (e) => {
        const term = normalizeCategory(e.target.value);

        if (!term) {
            closeOverlay();
            return;
        }

        if (term.length < 2) return;

        let html = '';

        const matchedHospitals = allHospitals.filter((hospital) =>
            normalizeCategory(hospital.name).includes(term) || normalizeCategory(hospital.location).includes(term)
        );

        matchedHospitals.forEach((hospital) => {
            html += renderHospitalCard(hospital);
        });

        const matchedDoctors = allDoctors.filter((doctor) =>
            normalizeCategory(doctor.name).includes(term) ||
            normalizeCategory(doctor.profession).includes(term) ||
            normalizeCategory(doctor.hospital).includes(term)
        );

        matchedDoctors.forEach((doctor) => {
            html += renderDoctorCard(doctor);
        });

        resultHeader.innerHTML = html
            ? `<h2>Results for "${e.target.value}"</h2>`
            : `<h2>No results for "${e.target.value}"</h2>`;
        resultsList.innerHTML = html;
        openOverlay();
    });
}

function filterByDisease(name, desc) {
    const selected = normalizeCategory(name);
    const matched = allDoctors.filter((doctor) => {
        const profession = normalizeCategory(doctor.profession);
        const bodyPart = normalizeCategory(doctor.body_part);
        return profession === selected || bodyPart === selected;
    });

    renderDoctorResults(matched, name, desc);
}

function filterByHospital(hospitalName) {
    const matched = allDoctors.filter(
        (doctor) => normalizeCategory(doctor.hospital) === normalizeCategory(hospitalName)
    );
    renderDoctorResults(matched, hospitalName, 'Our Top Specialists');
}

function toggleIdea(element) {
    document.querySelectorAll('.interactive-link').forEach((link) => {
        if (link !== element) link.classList.remove('active');
    });
    element.classList.toggle('active');
}

const revealOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px'
};

const revealOnScroll = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            entry.target.classList.add('reveal-active');
            observer.unobserve(entry.target);
        }
    });
}, revealOptions);

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('.disease-card, .info-bordered-box, .purpose-gap, .category-card, .middle-heading, .section-title, .footer-section')
        .forEach((element) => {
            element.classList.add('reveal-hidden');
            revealOnScroll.observe(element);
        });
});

window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    if (!nav) return;
    if (window.scrollY > 20) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

document.addEventListener('click', function (e) {
    if (!(e.target && e.target.classList.contains('btn-view') && e.target.innerText === 'Book Now')) {
        return;
    }

    const card = e.target.closest('.card');
    if (!card) return;

    const doctorData = {
        id: card.dataset.doctorId || '',
        name: card.querySelector('.doc-name')?.innerText || '',
        specialty: card.querySelector('.doc-specialty')?.innerText || '',
        hospital: card.querySelector('.doc-hospital')?.innerText || '',
        exp: card.querySelector('.doc-exp')?.innerText || '',
        time: card.querySelector('.doc-time')?.innerText || '',
        consultation_fee: card.dataset.consultationFee || '0'
    };

    localStorage.setItem('selectedDoctor', JSON.stringify(doctorData));

    const signInButton = document.querySelector('a[href="signin.php"]');
    if (signInButton) {
        window.location.href = 'signin.php?redirect=dashboard';
    } else {
        window.location.href = 'dashboard.php';
    }
});

function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('no-scroll');
    }
}

function switchAuth(type) {
    const loginSec = document.getElementById('loginSection');
    const signupSec = document.getElementById('signupSection');
    const toggleContainer = document.querySelector('.auth-toggle-container');
    const btnLogin = document.getElementById('btn-login');
    const btnSignup = document.getElementById('btn-signup');

    if (!loginSec || !signupSec || !toggleContainer || !btnLogin || !btnSignup) return;

    if (type === 'signup') {
        loginSec.style.display = 'none';
        signupSec.style.display = 'block';
        toggleContainer.classList.add('toggle-signup-active');
        btnSignup.classList.add('active');
        btnLogin.classList.remove('active');
    } else {
        loginSec.style.display = 'block';
        signupSec.style.display = 'none';
        toggleContainer.classList.remove('toggle-signup-active');
        btnLogin.classList.add('active');
        btnSignup.classList.remove('active');
    }
}

window.addEventListener('pageshow', function () {
    localStorage.removeItem('selectedDoctor');
});

function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.classList.toggle('show');
}

window.onclick = function (event) {
    if (!event.target.matches('.profile-trigger, .profile-trigger *')) {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown && dropdown.classList.contains('show')) {
            dropdown.classList.remove('show');
        }
    }
};
