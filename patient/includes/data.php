<?php
include_once __DIR__ . '/db.php';

$hospitals = [];
$doctors = [];

$specialties = [
    "Cardiologist" => "Heart",
    "Neurologist" => "Brain",
    "Orthopedic" => "Bones",
    "Orthopedist" => "Bones",
    "Dermatologist" => "Skin",
    "Pediatrician" => "Child Care",
    "Oncologist" => "Cancer",
    "Gastroenterologist" => "Digestive",
    "Ophthalmologist" => "Eyes",
    "Nephrologist" => "Kidneys",
    "Pulmonologist" => "Lungs",
    "Endocrinologist" => "Hormones",
    "Psychiatrist" => "Mind",
    "ENT Specialist" => "Ear/Nose",
    "ENT" => "Ear/Nose",
    "Urologist" => "Urinary",
    "Dentist" => "Dental",
    "Cardiology" => "Heart",
    "Neurology" => "Brain",
    "Orthopedics" => "Bones",
    "Dermatology" => "Skin",
    "Pediatrics" => "Child Care",
    "Oncology" => "Cancer",
    "Gastroenterology" => "Digestive",
    "Ophthalmology" => "Eyes",
    "Nephrology" => "Kidneys",
    "Pulmonology" => "Lungs",
    "Endocrinology" => "Hormones",
    "Psychiatry" => "Mind",
    "Urology" => "Urinary",
    "Dentistry" => "Dental"
];

$diseases = [
    ["name" => "Cardiology", "part" => "Heart", "icon" => "fa-heart", "desc" => "Comprehensive care for heart-related ailments."],
    ["name" => "Neurology", "part" => "Brain", "icon" => "fa-brain", "desc" => "Expert diagnosis for nervous system disorders."],
    ["name" => "Orthopedics", "part" => "Bones", "icon" => "fa-bone", "desc" => "Specialized treatment for skeletal and joint health."],
    ["name" => "Dermatology", "part" => "Skin", "icon" => "fa-hand-dots", "desc" => "Clinical and cosmetic skin care solutions."],
    ["name" => "Pediatrics", "part" => "Child Care", "icon" => "fa-baby", "desc" => "Dedicated healthcare for infants and children."],
    ["name" => "Oncology", "part" => "Cancer", "icon" => "fa-ribbon", "desc" => "Advanced cancer diagnosis and treatment plans."],
    ["name" => "Gastroenterology", "part" => "Digestive", "icon" => "fa-bacteria", "desc" => "Treatment for digestive and liver disorders."],
    ["name" => "Ophthalmology", "part" => "Eyes", "icon" => "fa-eye", "desc" => "Vision correction and eye disease management."],
    ["name" => "Nephrology", "part" => "Kidneys", "icon" => "fa-microscope", "desc" => "Specialized care for kidney-related conditions."],
    ["name" => "Pulmonology", "part" => "Lungs", "icon" => "fa-lungs", "desc" => "Treatment for respiratory and lung diseases."],
    ["name" => "Endocrinology", "part" => "Hormones", "icon" => "fa-droplet", "desc" => "Management of hormonal and thyroid disorders."],
    ["name" => "Psychiatry", "part" => "Mind", "icon" => "fa-head-side-virus", "desc" => "Support and treatment for mental well-being."],
    ["name" => "ENT", "part" => "Ear/Nose", "icon" => "fa-ear-listen", "desc" => "Expertise in Ear, Nose, and Throat health."],
    ["name" => "Urology", "part" => "Urinary", "icon" => "fa-toilet-paper", "desc" => "Clinical care for urinary tract conditions."],
    ["name" => "Dentistry", "part" => "Dental", "icon" => "fa-tooth", "desc" => "Complete oral hygiene and dental procedures."]
];

function normalizeSpecialty($specialty)
{
    $specialty = trim((string) $specialty);

    $map = [
        'Cardiologist' => 'Cardiology',
        'Neurologist' => 'Neurology',
        'Orthopedic' => 'Orthopedics',
        'Orthopedist' => 'Orthopedics',
        'Dermatologist' => 'Dermatology',
        'Pediatrician' => 'Pediatrics',
        'Oncologist' => 'Oncology',
        'Gastroenterologist' => 'Gastroenterology',
        'Ophthalmologist' => 'Ophthalmology',
        'Nephrologist' => 'Nephrology',
        'Pulmonologist' => 'Pulmonology',
        'Endocrinologist' => 'Endocrinology',
        'Psychiatrist' => 'Psychiatry',
        'ENT Specialist' => 'ENT',
        'Urologist' => 'Urology',
        'Dentist' => 'Dentistry',
    ];

    return $map[$specialty] ?? $specialty;
}

function specialtyBodyPart($specialty, array $specialties)
{
    $specialty = trim((string) $specialty);
    $normalized = normalizeSpecialty($specialty);

    if (isset($specialties[$normalized])) {
        return $specialties[$normalized];
    }

    if (isset($specialties[$specialty])) {
        return $specialties[$specialty];
    }

    return $normalized;
}

if ($conn) {
    $hospitalResult = mysqli_query($conn, "SELECT id, name, location FROM hospitals ORDER BY name ASC");
    if ($hospitalResult) {
        while ($row = mysqli_fetch_assoc($hospitalResult)) {
            $hospitals[] = [
                "id" => (int) $row['id'],
                "name" => trim($row['name']),
                "location" => trim($row['location']),
            ];
        }
    }

    $doctorSql = "SELECT id, full_name, specialty, hospital, experience, available_time, consultation_fee, body_part
                  FROM doctors
                  WHERE is_active = 1
                    AND status = 'available'
                  ORDER BY specialty ASC, full_name ASC";
    $doctorResult = mysqli_query($conn, $doctorSql);
    if ($doctorResult) {
        while ($row = mysqli_fetch_assoc($doctorResult)) {
            $rawSpecialty = trim((string) $row['specialty']);
            $normalizedSpecialty = normalizeSpecialty($rawSpecialty);

            $doctors[] = [
                "id" => (int) $row['id'],
                "name" => trim($row['full_name']),
                "profession" => $normalizedSpecialty,
                "hospital" => trim((string) $row['hospital']),
                "exp" => trim((string) ($row['experience'] ?: 'N/A')),
                "time" => trim((string) ($row['available_time'] ?: 'Not Available')),
                "body_part" => trim((string) ($row['body_part'] ?: specialtyBodyPart($rawSpecialty, $specialties))),
                "consultation_fee" => (int) ($row['consultation_fee'] ?? 0),
            ];
        }
    }
}
?>
