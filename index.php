<?php
include "db.php";

$success = "";
$error = "";
$application_id = "";

if($_SERVER["REQUEST_METHOD"]==="POST") {
    $full_name = trim($_POST["full_name"]??"");
    $dob = trim($_POST["dob"]??"");
    $email = trim($_POST["email"]??"");
    $phone = trim($_POST["phone"]??"");
    $gender = trim($_POST["gender"]??"");

    $college = trim($_POST["college"]??"");
    $course = trim($_POST["course"]??"");
    $current_year = trim($_POST["current_year"]??"");
    $percentage = trim($_POST["percentage"]??"");

    $annual_income = trim($_POST["annual_income"]??"");
    $scholarship_category = trim($_POST["scholarship_category"]??"");

    $address = trim($_POST["address"]??"");

    if (
        empty($full_name) ||
        empty($dob) ||
        empty($email) ||
        empty($college) ||
        empty($course) ||
        empty($current_year) ||
        $percentage === "" ||
        $annual_income === "" ||
        empty($scholarship_category) ||
        empty($address)
    ) {
        $error = "Please fill all required fields.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    elseif (!is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
        $error = "Percentage must be between 0 and 100.";
    }

    elseif (!is_numeric($annual_income) || $annual_income < 0) {
        $error = "Annual income cannot be negative.";
    }

    if (empty($error)) {
        $upload_folder = "uploads/";

        if (!is_dir($upload_folder)) {

            if (!mkdir($upload_folder, 0777, true)) {
                $error = "Unable to create uploads folder.";
            }
        }

        $allowed_extensions = [
            "pdf",
            "jpg",
            "jpeg",
            "png"
        ];

        if (empty($error)) {
            if (
                !isset($_FILES["marksheet"]) ||
                $_FILES["marksheet"]["error"] !== UPLOAD_ERR_OK
            ) {
                $error = "Please upload your marksheet.";
            }

            else {
                $marksheet = $_FILES["marksheet"];

                $marksheet_extension = strtolower(
                    pathinfo(
                        $marksheet["name"],
                        PATHINFO_EXTENSION
                    )
                );

                if (
                    !in_array(
                        $marksheet_extension,
                        $allowed_extensions,
                    )
                ) {
                    $error = "Invalid marksheet file, Only PDF, JPG, JPEG and PNG files are allowed.";
                }

                elseif ($marksheet["size"] > 5 * 1024 * 1024) {
                    $error = "Marksheet must be less than 5MB.";
                }

                else{
                    $marksheet_name = "marksheet_" . time() . "_" . uniqid() . "." . $marksheet_extension;
                }
            }
        }

        if (empty($error)) {
            
            if (
                !isset($_FILES["income_certificate"]) ||
                $_FILES["income_certificate"]["error"] !== UPLOAD_ERR_OK
            ) {
                $error = "Please upload income certificate.";
            }
            
            else {
                $income_certificate = 
                    $_FILES["income_certificate"];

                $income_extension = strtolower(
                    pathinfo(
                        $income_certificate["name"],
                        PATHINFO_EXTENSION
                    )
                );

                if (
                    !in_array(
                        $income_extension,
                        $allowed_extensions,
                        true
                    )
                ) {
                    $error = "Invalid  income certificate. Only PDF, JPG, JPEG and PNG files are allowed.";
                }

                elseif ($income_certificate["size"] > 5 * 1024 * 1024) {
                    $error = "Income certificate must be less than 5MB.";
                }

                else {
                    $income_certificate_name = "income_" . time() . "_" . uniqid() . "." . $income_extension;
                }
            }
        }

        if (empty($error)) {
            $marksheet_path = 
                $upload_folder . $marksheet_name;

            $income_certificate_path = 
                $upload_folder . $income_certificate_name;

            if (
                !move_uploaded_file (
                    $marksheet["tmp_name"],
                    $marksheet_path
                )
            ) {
                $error = "Unable to upload marksheet.";
            }

            elseif (
                !move_uploaded_file (
                    $income_certificate["tmp_name"],
                    $income_certificate_path
                )
            ) {
                
                if(file_exists($marksheet_path)) {
                    unlink($marksheet_path);
                }
                $error = "Unable to upload income certificate.";
            }
        }

        if (empty($error)) {
            $application_id = 
                "SCH" . 
                date("Y") .
                strtoupper(
                    substr(
                        uniqid(),
                        -6
                    )
                );
            
            $sql = "INSERT INTO applications
                   (
                    application_id,
                    full_name,
                    dob,
                    email,
                    phone,
                    gender,
                    college,
                    course,
                    current_year,
                    percentage,
                    annual_income,
                    scholarship_category,
                    address,
                    marksheet,
                    income_certificate
                   )

                   VALUES
                   (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?
                   )
                ";

            $stmt = $conn -> prepare($sql);

            if (!$stmt) {
                $error = "Database error: " . $conn -> error;
            }

            else {
                $stmt -> bind_param (
                    "sssssssssddssss",

                        $application_id,
                        $full_name,
                        $dob,
                        $email,
                        $phone,
                        $gender,

                        $college,
                        $course,
                        $current_year,
                        $percentage,

                        $annual_income,
                        $scholarship_category,

                        $address,
                        $marksheet_name,
                        $income_certificate_name
                );

                if ($stmt -> execute()) {
                    $success = 
                        "Application submitted successfully!";
                }

                else {
                    $error = 
                        "Unable to save application:" .
                        $stmt -> error;

                        if (file_exists($marksheet_path)) {
                            unlink($marksheet_path);
                        }

                        if (file_exists($income_certificate_path)) {
                            unlink($income_certificate_path);
                        }
                    }
                    
                $stmt -> close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    
    <head>
        <meta charset="UTF-8">
        <title>Scholarship Application</title>
    </head>

    <body>
        
        <div class="container">
            
            <div class="header">
                <div class="icon"></div>

                <h1>Scholarship Application</h1>

                <p class="description">
                    Please fill in all the details carefully to apply for the scholarship.
                </p>
            </div>

            <?php if(!empty($success)): ?>
                <div class="alert success">
                    
                    <?php 
                    echo htmlspecialchars($success);
                    ?>

                    <br><br>
                    <strong>Application ID:</strong>

                    <?php
                    echo htmlspecialchars($application_id);
                    ?>
                </div>

            <?php endif; ?>

            <?php if(!empty($error)): ?>
                <div class="alert error">

                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </div>

            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="section">
                    <h2>
                        <span class="number">1</span>
                        Student Details
                    </h2>

                    <div class="grid">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" 
                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob"  
                            value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="Enter phone number"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            
                            <label>Gender</label>
                            <div class="radio-group">

                                <label>
                                    <input type="radio" name="gender" value="Male"
                                    <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'checked' : ''; ?>>
                                    Male
                                </label>

                                <label>
                                    <input type="radio" name="gender" value="Female"
                                    <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'checked' : ''; ?>>
                                    Female
                                </label>

                                <label>
                                    <input type="radio" name="gender" value="Other"
                                    <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'checked' : ''; ?>>
                                    Other
                                </label>

                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>
                        <span class="number">2</span>
                        Academic Details
                    </h2>

                    <div class="grid">

                        <div class="form-group">
                            <label for="college">College/Institution</label>
                            <input type="text" id="college" name="college" placeholder="Enter college name"
                            value="<?php echo htmlspecialchars($_POST['college'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="course">Course</label>
                            <select id="course" name="course" required>

                                <option value="">Select Course</option>

                                <option value="BCA" <?php echo (($_POST['course'] ?? '') === 'BCA') ? 'selected' : ''; ?>>
                                    BCA
                                </option>

                                <option value="BSc" <?php echo (($_POST['course'] ?? '') === 'BSc') ? 'selected' : ''; ?>>
                                    BSc
                                </option>

                                <option value="BCOM" <?php echo (($_POST['course'] ?? '') === 'BCOM') ? 'selected' : ''; ?>>
                                    BCOM
                                </option>

                                <option value="BE" <?php echo (($_POST['course'] ?? '') === 'BE') ? 'selected' : ''; ?>>
                                    BE
                                </option>

                                <option value="BBA" <?php echo (($_POST['course'] ?? '') === 'BBA') ? 'selected' : ''; ?>>
                                    BBA
                                </option>

                                <option value="MCA" <?php echo (($_POST['course'] ?? '') === 'MCA') ? 'selected' : ''; ?>>
                                    MCA
                                </option>

                                <option value="MBA" <?php echo (($_POST['course'] ?? '') === 'MBA') ? 'selected' : ''; ?>>
                                    MBA
                                </option>
                        
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="current_year">Current Year</label>
                            <select id="current_year" name="current_year" required>

                                <option value="">Select Year</option>

                                <option value="1st Year" <?php echo (($_POST['current_year'] ?? '') === '1st Year') ? 'selected' : ''; ?>>
                                    1st Year
                                </option>

                                <option value="2nd Year" <?php echo (($_POST['current_year'] ?? '') === '2nd Year') ? 'selected' : ''; ?>>
                                    2nd Year
                                </option>

                                <option value="3rd Year" <?php echo (($_POST['current_year'] ?? '') === '3rd Year') ? 'selected' : ''; ?>>
                                    3rd Year
                                </option>

                                <option value="4th Year" <?php echo (($_POST['current_year'] ?? '') === '4th Year') ? 'selected' : ''; ?>>
                                    4th Year
                                </option>

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="percentage">Previous Percentage</label>
                            <input type="number" id="percentage" name="percentage" placeholder="Example: 78.5"
                            min="0" max="100" step="0.01"
                            value="<?php echo htmlspecialchars($_POST['percentage'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2>
                        <span class="number">3</span>
                        Financial Details
                    </h2>

                    <div class="grid">
                        
                        <div class="form-group">
                            <label for="annual_income">Annual Family Income</label>
                            <input type="number" id="annual_income" name="annual_income" placeholder="Enter annual income"
                            min="0" step="0.01"
                            value="<?php echo htmlspecialchars($_POST['annual_income'] ?? ''); ?>"  required>
                        </div>

                        <div class="form-group">
                            <label for="scholarship_category">Scholarship Category</label>
                            <select id="scholarship_category" name="scholarship_category" required>

                                <option value="">Select Category</option>

                                <option value="Merit Scholarship" <?php echo (($_POST['scholarship_category'] ?? '') === 'Merit Scholarship') ? 'selected' : ''; ?>>
                                    Merit Scholarship
                                </option>

                                <option value="Need Based Scholarship" <?php echo (($_POST['scholarship_category'] ?? '') === 'Need Based Scholarship') ? 'selected' : ''; ?>>
                                    Need Based Scholarship
                                </option>

                                <option value="Sports Scholarship" <?php echo (($_POST['scholarship_category'] ?? '') === 'Sports Scholarship') ? 'selected' : ''; ?>>
                                    Sports Scholarship
                                </option>

                                <option value="Minority Scholarship" <?php echo (($_POST['scholarship_category'] ?? '') === 'Minority Scholarship') ? 'selected' : ''; ?>>
                                    Minority Scholarship
                                </option>

                                <option value="Other" <?php echo (($_POST['scholarship_category'] ?? '') === 'Other') ? 'selected' : ''; ?>>
                                    Other
                                </option>

                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    
                    <h2>
                        <span class="number">4</span>
                        Address Details
                    </h2>

                    <div class="form-group">
                        <label for="address">Permanent Address</label>
                        <textarea id="address" name="address" placeholder="Enter your complete address" required>           <?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="section">
                    
                    <h2>
                        <span class="number">5</span>
                        Upload Documents
                    </h2>

                    <div class="grid">
                        <div class="form-group">
                            <label for="marksheet">Previous Marksheet</label>

                            <div class="file-box">
                                <input type="file" id="marksheet" name="marksheet"
                                accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>

                            <small>PDF/JPG/PNG - Maximum 5MB</small>
                        </div>

                        <div class="form-group">
                            <label for="income_certificate">Income Certificate</label>

                            <div class="file-box">
                                <input type="file" id="income_certificate" name="income_certificate"
                                accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>

                            <small>PDF/JPG/PNG - Maximum 5MB</small>
                        </div>
                    </div>
                </div>

                <div class="declaration">
                    <label>
                        <input type="checkbox" name="declaration" value="1" required>

                        <span>
                            I declare that the information provided in this application is 
                            true and correct to the best of my knowledge.
                        </span>
                    </label>
                </div>

                <div class="buttons">
                    <button type="reset" class="reset">Reset</button>
                    <button type="submit" class="submit">Apply for Scholarship</button>
                </div>

            </form>

            <div class="footer">
                Your application information is securely stored
            </div>

        </div>
    </body>
</html>