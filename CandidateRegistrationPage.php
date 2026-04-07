<?php
require_once 'db.php';

$success = "";
$error = "";

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Candidate profile submitted successfully.";
}

function clean_input($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

$first_name = "";
$last_name = "";
$email = "";
$phone = "";
$right_to_work_uk = "";
$share_code = "";
$job_preference = "";
$current_location = "";
$convenient_shift = "";
$working_flexible = "";
$experience_years = "";
$additional_notes = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name        = trim($_POST['first_name'] ?? '');
    $last_name         = trim($_POST['last_name'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $right_to_work_uk  = trim($_POST['right_to_work_uk'] ?? '');
    $share_code        = trim($_POST['share_code'] ?? '');
    $job_preference    = trim($_POST['job_preference'] ?? '');
    $current_location  = trim($_POST['current_location'] ?? '');
    $working_flexible  = trim($_POST['working_flexible'] ?? '');
    $experience_years  = trim($_POST['experience_years'] ?? '');
    $additional_notes  = trim($_POST['additional_notes'] ?? '');

    $convenient_shift = "";
    if (!empty($_POST['convenient_shift']) && is_array($_POST['convenient_shift'])) {
        $allowed_shifts = ['Morning', 'Evening', 'Night', 'Weekend'];
        $selected_shifts = array_intersect($_POST['convenient_shift'], $allowed_shifts);
        $convenient_shift = implode(', ', $selected_shifts);
    }

    if (
        empty($first_name) ||
        empty($last_name) ||
        empty($email) ||
        empty($phone) ||
        empty($right_to_work_uk) ||
        empty($job_preference) ||
        empty($working_flexible)
    ) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $cv_file_name = null;

        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['cv_file']['error'] === 0) {
                $allowed_extensions = ['pdf', 'doc', 'docx'];
                $file_name = $_FILES['cv_file']['name'];
                $file_tmp  = $_FILES['cv_file']['tmp_name'];
                $file_size = $_FILES['cv_file']['size'];
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = "Only PDF, DOC, and DOCX files are allowed.";
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $error = "CV file must be less than 5MB.";
                } else {
                    $upload_dir = __DIR__ . '/uploads/cvs/';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $cv_file_name = uniqid('cv_', true) . '.' . $file_ext;
                    $upload_path = $upload_dir . $cv_file_name;

                    if (!move_uploaded_file($file_tmp, $upload_path)) {
                        $error = "Failed to upload CV file.";
                    }
                }
            } else {
                $error = "There was an error uploading the CV file.";
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO candidates (
                first_name,
                last_name,
                email,
                phone,
                right_to_work_uk,
                share_code,
                job_preference,
                current_location,
                convenient_shift,
                working_flexible,
                experience_years,
                cv_file,
                additional_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssssss",
                    $first_name,
                    $last_name,
                    $email,
                    $phone,
                    $right_to_work_uk,
                    $share_code,
                    $job_preference,
                    $current_location,
                    $convenient_shift,
                    $working_flexible,
                    $experience_years,
                    $cv_file_name,
                    $additional_notes
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: CandidateRegistrationPage.php?success=1");
                    exit;
                } else {
                    $error = "Database error: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error = "Failed to prepare database statement.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Candidate Registration - Bridgeway Consulting</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{
      --primary:#2563eb;
      --primary-dark:#1d4ed8;
      --secondary:#7c3aed;
      --dark:#0f172a;
      --muted:#64748b;
      --soft:#f8fafc;
      --border:rgba(15,23,42,0.08);
      --success-bg:#ecfdf5;
      --success-text:#065f46;
      --danger-bg:#fef2f2;
      --danger-text:#991b1b;
    }

    *{
      box-sizing:border-box;
    }

    body{
      font-family:'Inter',sans-serif;
      color:var(--dark);
      background:
        radial-gradient(circle at top left, rgba(37,99,235,0.10), transparent 30%),
        radial-gradient(circle at top right, rgba(124,58,237,0.08), transparent 28%),
        linear-gradient(135deg, #eef4ff 0%, #f8fafc 52%, #ffffff 100%);
      min-height:100vh;
    }

    .page-wrap{
      padding: 50px 0 70px;
    }

    .hero-card,
    .form-card{
      background: rgba(255,255,255,0.88);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border);
      border-radius: 28px;
      box-shadow: 0 20px 50px rgba(15,23,42,0.08);
    }

    .hero-card{
      padding: 34px;
      height: 100%;
    }

    .form-card{
      padding: 34px;
    }

    .brand-pill{
      display:inline-flex;
      align-items:center;
      gap:10px;
      padding:10px 16px;
      border-radius:999px;
      background:rgba(37,99,235,0.10);
      color:var(--primary);
      font-size:14px;
      font-weight:700;
      margin-bottom:18px;
    }

    .hero-title{
      font-size: clamp(2rem, 4vw, 3.5rem);
      line-height:1.08;
      font-weight:800;
      margin-bottom:14px;
      letter-spacing:-0.03em;
    }

    .hero-text{
      color:var(--muted);
      font-size:1.03rem;
      line-height:1.7;
      margin-bottom:0;
    }

    .mini-stat{
      display:flex;
      align-items:flex-start;
      gap:14px;
      padding:16px 0;
      border-bottom:1px solid rgba(15,23,42,0.06);
    }

    .mini-stat:last-child{
      border-bottom:none;
      padding-bottom:0;
    }

    .mini-icon{
      width:46px;
      height:46px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(135deg, rgba(37,99,235,0.12), rgba(124,58,237,0.12));
      color:var(--primary);
      font-weight:800;
      flex-shrink:0;
    }

    .mini-stat h6{
      font-weight:700;
      margin-bottom:4px;
    }

    .mini-stat p{
      margin-bottom:0;
      color:var(--muted);
      font-size:0.94rem;
    }

    .form-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:24px;
      flex-wrap:wrap;
    }

    .form-title{
      margin:0;
      font-size:1.7rem;
      font-weight:800;
      letter-spacing:-0.02em;
    }

    .form-subtitle{
      color:var(--muted);
      margin:6px 0 0;
    }

    .section-box{
      background:var(--soft);
      border:1px solid #e6edf6;
      border-radius:22px;
      padding:22px;
      height:100%;
    }

    .section-title{
      font-size:1.05rem;
      font-weight:800;
      margin-bottom:16px;
    }

    .form-label{
      font-weight:700;
      font-size:0.95rem;
      margin-bottom:8px;
      color:#1e293b;
    }

    .form-control,
    .form-select{
      min-height:52px;
      border-radius:16px;
      border:1px solid #d7e2ef;
      padding:12px 16px;
      box-shadow:none !important;
      background:#fff;
    }

    textarea.form-control{
      min-height:auto;
      resize:vertical;
    }

    .form-control:focus,
    .form-select:focus{
      border-color:rgba(37,99,235,0.55);
      box-shadow:0 0 0 0.25rem rgba(37,99,235,0.12) !important;
    }

    .form-check{
      padding-left:1.9rem;
    }

    .form-check-input{
      width:1.1rem;
      height:1.1rem;
      margin-top:0.18rem;
      cursor:pointer;
    }

    .form-check-label{
      font-weight:600;
      color:#334155;
      cursor:pointer;
    }

    .alert-custom{
      border:none;
      border-radius:18px;
      padding:16px 18px;
      font-weight:600;
      margin-bottom:22px;
    }

    .alert-success-custom{
      background:var(--success-bg);
      color:var(--success-text);
    }

    .alert-danger-custom{
      background:var(--danger-bg);
      color:var(--danger-text);
    }

    .submit-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      flex-wrap:wrap;
      margin-top:10px;
    }

    .small-note{
      margin:0;
      color:var(--muted);
      font-size:0.92rem;
      max-width:700px;
    }

    .submit-btn{
      min-height:56px;
      border:none;
      border-radius:16px;
      padding:0 28px;
      font-weight:800;
      letter-spacing:0.2px;
      background:linear-gradient(135deg, var(--primary), var(--secondary));
      color:#fff;
      box-shadow:0 14px 30px rgba(37,99,235,0.22);
      transition:all 0.2s ease;
    }

    .submit-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 18px 36px rgba(37,99,235,0.26);
    }

    .required-star{
      color:#dc2626;
      margin-left:3px;
    }

    @media (max-width: 991.98px){
      .page-wrap{
        padding-top:30px;
      }

      .hero-card,
      .form-card{
        padding:24px;
        border-radius:22px;
      }

      .section-box{
        padding:18px;
        border-radius:18px;
      }

      .hero-title{
        font-size:2.2rem;
      }
    }
  </style>
</head>
<body>
  <div class="container page-wrap">
    <div class="row g-4 align-items-stretch mb-4">
      <div class="col-lg-7">
        <div class="hero-card">
          <div class="brand-pill">Bridgeway Consulting • Candidate Registration</div>
          <h1 class="hero-title">Register your profile and let us connect you with the right job opportunities.</h1>
          <p class="hero-text">
            Fill in your personal details, job preference, work eligibility, availability, and upload your CV.
            Our team will review your profile and match you with suitable employers.
          </p>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-card">
          <div class="mini-stat">
            <div class="mini-icon">1</div>
            <div>
              <h6>Simple registration</h6>
              <p>Easy form sections make the process smooth and professional.</p>
            </div>
          </div>

          <div class="mini-stat">
            <div class="mini-icon">2</div>
            <div>
              <h6>Useful employer data</h6>
              <p>Collect key details needed to help shortlist candidates faster.</p>
            </div>
          </div>

          <div class="mini-stat">
            <div class="mini-icon">3</div>
            <div>
              <h6>CV stored securely</h6>
              <p>Uploaded documents can be kept for later review in the admin dashboard.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-card">
      <div class="form-header">
        <div>
          <h2 class="form-title">Candidate Information Form</h2>
          <p class="form-subtitle">Please complete the details below so we can evaluate your profile properly.</p>
        </div>
      </div>

      <?php if (!empty($success)): ?>
        <div class="alert-custom alert-success-custom" id="successMessage">
          <?php echo clean_input($success); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert-custom alert-danger-custom">
          <?php echo clean_input($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
          <div class="col-12">
            <div class="section-box">
              <h5 class="section-title">Personal Information</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">First Name <span class="required-star">*</span></label>
                  <input type="text" name="first_name" class="form-control" value="<?php echo clean_input($first_name); ?>" placeholder="Enter first name" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Last Name <span class="required-star">*</span></label>
                  <input type="text" name="last_name" class="form-control" value="<?php echo clean_input($last_name); ?>" placeholder="Enter last name" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email Address <span class="required-star">*</span></label>
                  <input type="email" name="email" class="form-control" value="<?php echo clean_input($email); ?>" placeholder="Enter email address" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone Number <span class="required-star">*</span></label>
                  <input type="text" name="phone" class="form-control" value="<?php echo clean_input($phone); ?>" placeholder="Enter phone number" required>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="section-box">
              <h5 class="section-title">Work Eligibility & Job Preference</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Right to Work in UK <span class="required-star">*</span></label>
                  <select name="right_to_work_uk" class="form-select" required>
                    <option value="">Select option</option>
                    <option value="Yes" <?php echo ($right_to_work_uk === 'Yes') ? 'selected' : ''; ?>>Yes</option>
                    <option value="No" <?php echo ($right_to_work_uk === 'No') ? 'selected' : ''; ?>>No</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Share Code</label>
                  <input type="text" name="share_code" class="form-control" value="<?php echo clean_input($share_code); ?>" placeholder="Enter UK share code">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Job Preference <span class="required-star">*</span></label>
                  <select name="job_preference" class="form-select" required>
                    <option value="">Select preferred job</option>
                    <?php
                    $job_options = [
                      'Care Assistant',
                      'Warehouse Staff',
                      'Cleaner',
                      'Driver',
                      'Office Admin',
                      'Hospitality Staff',
                      'Retail Assistant'
                    ];
                    foreach ($job_options as $job) {
                        $selected = ($job_preference === $job) ? 'selected' : '';
                        echo '<option value="' . clean_input($job) . '" ' . $selected . '>' . clean_input($job) . '</option>';
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Current Location</label>
                  <input type="text" name="current_location" class="form-control" value="<?php echo clean_input($current_location); ?>" placeholder="Enter city or area">
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="section-box">
              <h5 class="section-title">Availability</h5>

              <?php
              $selected_shifts_arr = [];
              if (!empty($convenient_shift)) {
                  $selected_shifts_arr = array_map('trim', explode(',', $convenient_shift));
              }
              ?>

              <div class="mb-4">
                <label class="form-label d-block">Convenient Shift</label>
                <div class="d-flex flex-wrap gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="convenient_shift[]" value="Morning" id="morning" <?php echo in_array('Morning', $selected_shifts_arr) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="morning">Morning</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="convenient_shift[]" value="Evening" id="evening" <?php echo in_array('Evening', $selected_shifts_arr) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="evening">Evening</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="convenient_shift[]" value="Night" id="night" <?php echo in_array('Night', $selected_shifts_arr) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="night">Night</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="convenient_shift[]" value="Weekend" id="weekend" <?php echo in_array('Weekend', $selected_shifts_arr) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="weekend">Weekend</label>
                  </div>
                </div>
              </div>

              <div>
                <label class="form-label d-block">Working Flexible <span class="required-star">*</span></label>
                <div class="d-flex flex-wrap gap-4">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="working_flexible" value="Yes" id="flexibleYes" required <?php echo ($working_flexible === 'Yes') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="flexibleYes">Yes</label>
                  </div>

                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="working_flexible" value="No" id="flexibleNo" required <?php echo ($working_flexible === 'No') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="flexibleNo">No</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="section-box">
              <h5 class="section-title">Additional Information</h5>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Years of Experience</label>
                  <select name="experience_years" class="form-select">
                    <option value="">Select experience</option>
                    <?php
                    $experience_options = ['Fresher', '1 Year', '2 Years', '3 Years', '5+ Years'];
                    foreach ($experience_options as $exp) {
                        $selected = ($experience_years === $exp) ? 'selected' : '';
                        echo '<option value="' . clean_input($exp) . '" ' . $selected . '>' . clean_input($exp) . '</option>';
                    }
                    ?>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Upload CV</label>
                  <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx">
                  <small class="text-muted">Allowed formats: PDF, DOC, DOCX. Max size: 5MB.</small>
                </div>

                <div class="col-12">
                  <label class="form-label">Additional Notes</label>
                  <textarea name="additional_notes" class="form-control" rows="5" placeholder="Write any extra details here..."><?php echo clean_input($additional_notes); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="submit-row">
              <p class="small-note">
                By submitting this form, the candidate agrees to share profile details for recruitment and job-matching purposes.
              </p>
              <button type="submit" class="submit-btn">Submit Candidate Profile</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    setTimeout(function () {
      const successBox = document.getElementById('successMessage');
      if (successBox) {
        successBox.style.transition = 'opacity 0.5s ease';
        successBox.style.opacity = '0';
        setTimeout(function () {
          successBox.style.display = 'none';
        }, 500);
      }
    }, 3000);
  </script>
</body>
</html>