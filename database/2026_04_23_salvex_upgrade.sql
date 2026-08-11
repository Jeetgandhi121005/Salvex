CREATE TABLE IF NOT EXISTS medicines (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255) DEFAULT NULL,
    strength VARCHAR(100) DEFAULT NULL,
    form VARCHAR(100) DEFAULT NULL,
    manufacturer VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_medicines_name (name)
);

CREATE TABLE IF NOT EXISTS consultations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    appointment_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    patient_id INT(11) NOT NULL,
    reason_for_visit TEXT DEFAULT NULL,
    diagnosis TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_consultations_appointment (appointment_id),
    KEY idx_consultations_doctor (doctor_id),
    KEY idx_consultations_patient (patient_id),
    CONSTRAINT fk_consultations_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE,
    CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE CASCADE,
    CONSTRAINT fk_consultations_patient FOREIGN KEY (patient_id) REFERENCES users (id) ON DELETE CASCADE
);

ALTER TABLE billing
    ADD COLUMN IF NOT EXISTS paid_at DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE prescriptions
    ADD COLUMN IF NOT EXISTS consultation_id INT(11) DEFAULT NULL AFTER appointment_id,
    ADD COLUMN IF NOT EXISTS doctor_id INT(11) DEFAULT NULL AFTER consultation_id,
    ADD COLUMN IF NOT EXISTS patient_id INT(11) DEFAULT NULL AFTER doctor_id,
    ADD COLUMN IF NOT EXISTS medicine_id INT(11) DEFAULT NULL AFTER patient_id,
    ADD COLUMN IF NOT EXISTS custom_entry TINYINT(1) NOT NULL DEFAULT 0 AFTER medicine_id;

ALTER TABLE medical_tests
    ADD COLUMN IF NOT EXISTS consultation_id INT(11) DEFAULT NULL AFTER appointment_id,
    ADD COLUMN IF NOT EXISTS doctor_id INT(11) DEFAULT NULL AFTER consultation_id,
    ADD COLUMN IF NOT EXISTS patient_id INT(11) DEFAULT NULL AFTER doctor_id,
    ADD COLUMN IF NOT EXISTS recommended_notes TEXT DEFAULT NULL AFTER test_name;

ALTER TABLE soap_notes
    ADD UNIQUE KEY IF NOT EXISTS uk_soap_notes_appointment (appointment_id);

ALTER TABLE care_instructions
    ADD UNIQUE KEY IF NOT EXISTS uk_care_instructions_appointment (appointment_id);

ALTER TABLE prescriptions
    ADD KEY IF NOT EXISTS idx_prescriptions_consultation (consultation_id),
    ADD KEY IF NOT EXISTS idx_prescriptions_doctor (doctor_id),
    ADD KEY IF NOT EXISTS idx_prescriptions_patient (patient_id),
    ADD KEY IF NOT EXISTS idx_prescriptions_medicine (medicine_id),
    ADD CONSTRAINT fk_prescriptions_consultation FOREIGN KEY (consultation_id) REFERENCES consultations (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_prescriptions_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_prescriptions_patient FOREIGN KEY (patient_id) REFERENCES users (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_prescriptions_medicine FOREIGN KEY (medicine_id) REFERENCES medicines (id) ON DELETE SET NULL;

ALTER TABLE medical_tests
    ADD KEY IF NOT EXISTS idx_medical_tests_consultation (consultation_id),
    ADD KEY IF NOT EXISTS idx_medical_tests_doctor (doctor_id),
    ADD KEY IF NOT EXISTS idx_medical_tests_patient (patient_id),
    ADD CONSTRAINT fk_medical_tests_consultation FOREIGN KEY (consultation_id) REFERENCES consultations (id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_medical_tests_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_medical_tests_patient FOREIGN KEY (patient_id) REFERENCES users (id) ON DELETE SET NULL;

ALTER TABLE appointments
    ADD KEY IF NOT EXISTS idx_appt_doctor_date_time_status (doctor_id, appointment_date, appointment_time, status);

ALTER TABLE doctors
    ADD KEY IF NOT EXISTS idx_doctors_active_status (is_active, status),
    ADD KEY IF NOT EXISTS idx_doctors_hospital_specialty (hospital, specialty);

ALTER TABLE billing
    ADD KEY IF NOT EXISTS idx_billing_status_date (status, billing_date),
    ADD KEY IF NOT EXISTS idx_billing_appointment (appointment_id);

UPDATE billing b
LEFT JOIN appointments a ON a.id = b.appointment_id
SET b.appointment_id = NULL
WHERE b.appointment_id IS NOT NULL
  AND a.id IS NULL;

ALTER TABLE billing
    ADD CONSTRAINT fk_billing_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL;

INSERT IGNORE INTO medicines (name, generic_name, strength, form, manufacturer) VALUES
    ('Paracetamol 500mg', 'Paracetamol', '500mg', 'Tablet', 'Generic'),
    ('Ibuprofen 400mg', 'Ibuprofen', '400mg', 'Tablet', 'Generic'),
    ('Amoxicillin 250mg', 'Amoxicillin', '250mg', 'Capsule', 'Generic'),
    ('Pantoprazole 40mg', 'Pantoprazole', '40mg', 'Tablet', 'Generic'),
    ('Metformin 500mg', 'Metformin', '500mg', 'Tablet', 'Generic'),
    ('Aspirin 75mg', 'Aspirin', '75mg', 'Tablet', 'Generic'),
    ('Atorvastatin 10mg', 'Atorvastatin', '10mg', 'Tablet', 'Generic'),
    ('Thyroxine 50mcg', 'Levothyroxine', '50mcg', 'Tablet', 'Generic'),
    ('Vitamin D3', 'Cholecalciferol', '60000 IU', 'Capsule', 'Generic'),
    ('Azithromycin 500mg', 'Azithromycin', '500mg', 'Tablet', 'Generic'),
    ('Cetirizine 10mg', 'Cetirizine', '10mg', 'Tablet', 'Generic'),
    ('Ondansetron 4mg', 'Ondansetron', '4mg', 'Tablet', 'Generic');
