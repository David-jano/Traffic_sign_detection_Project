<?php
session_start();
include('connection.php');

$user_created_successfully = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN with username and password (for Learners)
    if (isset($_POST['logemail']) && isset($_POST['logpass'])) {
        $username = trim($_POST['logemail']);
        $password = trim($_POST['logpass']);

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // IMPORTANT: For security, use password_verify() with hashed passwords.
            if ($user['password'] === $password) {
                $_SESSION['user'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "User not found.";
        }

    // LOGIN with license number (for Drivers)
    } elseif (isset($_POST['license_login'])) {
        $licenseNo = trim($_POST['license_login']);

        $stmt = $conn->prepare("SELECT * FROM users WHERE license_number = ?");
        $stmt->bind_param("s", $licenseNo);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "License not found or not registered.";
        }

    // SIGNUP for both Learner and Driver
    } elseif (isset($_POST['signup_username'])) {
        $fullname = trim($_POST['logname']);
        $username = trim($_POST['signup_username']);
        $password = trim($_POST['signup_password']);
        $confirm = trim($_POST['signup_confirm_password']);
        $role = $_POST['role'] ?? '';
        $license = $_POST['license_number'] ?? null;

        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            // If role is Driver, verify license with API
            if ($role === "Driver") {
                $api = file_get_contents("http://rwandalicensehub.atwebpages.com/license.php");
                $data = json_decode($api, true);
                $match = false;

                foreach ($data as $entry) {
                    if (strcasecmp($entry['Fullname'], $fullname) === 0 && $entry['licenceNo'] === $license) {
                        $match = true;
                        break;
                    }
                }

                if (!$match) {
                    $error = "License number does not match the  names or not Issued.";
                }
            }

            if (!$error) {
                // Check if username already exists
                $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($res && $res->num_rows > 0) {
                    $error = "Username already exists.";
                } else {
                    // Insert user (include license if role is Driver)
                    $stmt = $conn->prepare("INSERT INTO users (Fullname, username, password, role, license_number) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $fullname, $username, $password, $role, $license);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        $user_created_successfully = true;
                    } else {
                        $error = "Failed to create account.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link rel="stylesheet" href="https://unpkg.com/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/bs-brain@2.0.4/components/logins/login-10/assets/css/login-10.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
  <link rel="stylesheet" href="stylo.css" />
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="script.js" defer></script>
</head>
<body>

<div class="section">
  <div class="container">
    <div class="row full-height justify-content-center">
      <div class="col-12 text-center align-self-center py-5">
        <div class="section pb-5 pt-5 pt-sm-2 text-center">
          <h6 class="mb-0 pb-3"><span>Log In </span><span>Sign Up</span></h6>
          <input class="checkbox" type="checkbox" id="reg-log" name="reg-log" />
          <label for="reg-log"></label>
          
          <div class="card-3d-wrap mx-auto">
            <div class="card-3d-wrapper">

              <!-- Login Card -->
              <div class="card-front">
                <div class="center-wrap">
                  <div class="section text-center">
                    <h4 class="mb-4 pb-3">Log In</h4>

                    <!-- Toggle Between Login Methods -->
                    <div class="mb-3">
                      <button id="toggleUserLogin" class="btn btn-sm btn-outline-light me-2 active" type="button">Use Username</button>
                      <button id="toggleLicenseLogin" class="btn btn-sm btn-outline-light" type="button">Use License No</button>
                    </div>

                    <!-- Username Login -->
                    <div id="userLoginForm">
                      <form action="login.php" method="POST">
                        <div class="form-group">
                          <input type="text" name="logemail" class="form-style" placeholder="Username" id="logemail" autocomplete="off" required />
                          <i class="input-icon uil uil-at"></i>
                        </div>
                        <div class="form-group mt-2">
                          <input type="password" name="logpass" class="form-style" placeholder="Password" id="logpass" autocomplete="off" required />
                          <i class="input-icon uil uil-lock-alt"></i>
                        </div>
                        <button type="submit" class="btn mt-4">Login</button>
                      </form>
                      <p class="mb-0 mt-4 text-center"><a href="#0" class="link">Forgot your password?</a></p>
                    </div>

                    <!-- License Login -->
                    <div id="licenseLoginForm" style="display: none;">
                      <form action="login.php" method="POST">
                        <div class="form-group">
                          <input type="text" name="license_login" class="form-style" placeholder="License Number" id="licenseInput" autocomplete="off" required />
                          <i class="input-icon uil uil-car"></i>
                        </div>
                        <button type="submit" class="btn mt-4">Login</button>
                      </form>
                    </div>

                  </div>
                </div>
              </div>

              <!-- Signup Card -->
              <div class="card-back">
                <div class="center-wrap">
                  <div class="section text-center">
                    <h4 class="mb-4 pb-3">Sign Up</h4>

                    <form action="" method="POST">
                      <div class="form-group">
                        <input type="text" name="logname" class="form-style" placeholder="Full names" id="logname" autocomplete="off" required />
                        <i class="input-icon uil uil-user"></i>
                      </div>

                      <div class="form-group mt-2">
                        <select name="role" id="roleSelect" class="form-style" required>
                          <option value="">--Role--</option>
                          <option value="Driver">Driver</option>
                          <option value="Learner">Learner</option>
                        </select>
                        <i class="input-icon uil uil-user"></i>
                      </div>

                      <!-- Hidden license input (shows when Driver selected) -->
                      <div class="form-group mt-2" id="licenseField" style="display: none;">
                        <input type="text" name="license_number" class="form-style" placeholder="License Number" autocomplete="off" />
                        <i class="input-icon uil uil-car"></i>
                      </div>

                      <div class="form-group mt-2">
                        <input type="text" name="signup_username" class="form-style" placeholder="Choose Username" id="signupUsername" autocomplete="off" required />
                        <i class="input-icon uil uil-user"></i>
                      </div>

                      <div class="form-group mt-2">
                        <input type="password" name="signup_password" class="form-style" placeholder="Choose Password" id="signupPassword" autocomplete="off" required />
                        <i class="input-icon uil uil-lock-alt"></i>
                      </div>

                      <div class="form-group mt-2">
                        <input type="password" name="signup_confirm_password" class="form-style" placeholder="Confirm Password" id="signupConfirmPassword" autocomplete="off" required />
                        <i class="input-icon uil uil-lock-alt"></i>
                      </div>

                      <button type="submit" class="btn mt-4">Create</button>
                    </form>
                  </div>
                </div>
              </div>

            </div>
          </div><!-- card-3d-wrap -->

        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Toggle login methods
  document.getElementById('toggleUserLogin').addEventListener('click', function() {
    this.classList.add('active');
    document.getElementById('toggleLicenseLogin').classList.remove('active');
    document.getElementById('userLoginForm').style.display = 'block';
    document.getElementById('licenseLoginForm').style.display = 'none';
  });

  document.getElementById('toggleLicenseLogin').addEventListener('click', function() {
    this.classList.add('active');
    document.getElementById('toggleUserLogin').classList.remove('active');
    document.getElementById('userLoginForm').style.display = 'none';
    document.getElementById('licenseLoginForm').style.display = 'block';
  });

  // Show license field only when role Driver is selected
  document.getElementById('roleSelect').addEventListener('change', function() {
    if (this.value === 'Driver') {
      document.getElementById('licenseField').style.display = 'block';
      document.querySelector('#licenseField input').setAttribute('required', 'required');
    } else {
      document.getElementById('licenseField').style.display = 'none';
      document.querySelector('#licenseField input').removeAttribute('required');
    }
  });
</script>

<?php if ($error): ?>
<script>
  Swal.fire({
    position: 'top-end',
    icon: 'error',
    title: 'Error',
    text: <?php echo json_encode($error); ?>,
    showConfirmButton: false,
    timer: 3000,
   
  });
</script>
<?php elseif ($user_created_successfully): ?>
<script>
  Swal.fire({
    position: 'top-end',
    icon: 'success',
    title: 'Account created',
    text: 'Your account has been created successfully.',
    showConfirmButton: false,
    timer: 3000,
  });
</script>
<?php endif; ?>

</body>
</html>
