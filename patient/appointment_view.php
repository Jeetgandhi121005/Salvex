<?php
// Database connection aur session check zaroori hai
include 'config.php'; // Aapki DB connection file ka naam

$user_id = $_SESSION['user_id']; // Login user ki ID

// Database se appointments nikalna
$query = "SELECT * FROM appointments WHERE user_id = '$user_id' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<div class="appointments-container">
    <h2 style="margin-bottom: 20px;">My Appointments</h2>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="appointment-list" style="display: flex; flex-direction: column; gap: 15px;">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="appointment-card" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #2563eb;"><?php echo $row['doctor_name']; ?></h3>
                        <p style="color: #64748b; font-size: 14px;"><?php echo $row['specialty']; ?></p>
                        <p class="hospital-name" style="color: #64748b; font-size: 14px; margin-top: 5px;">
                            <i class="fa-solid fa-hospital" style="margin-right: 5px;"></i> 
                            <?php echo $row['hospital_name']; ?>
                        </p>
                    </div>
                        <p style="margin-top: 5px;"><strong>Date:</strong> <?php echo date('d M Y', strtotime($row['date'])); ?></p>
                    </div>  
                    <div style="text-align: right;">
                        <span style="background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">Confirmed</span>
                        <p style="margin-top: 10px; font-weight: bold;"><?php echo $row['time']; ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px;">
            <i class="fa-solid fa-calendar-xmark" style="font-size: 50px; color: #cbd5e1;"></i>
            <p style="margin-top: 15px; color: #64748b;">No appointments booked yet.</p>
        </div>
    <?php endif; ?>
</div>