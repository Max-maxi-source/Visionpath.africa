<?php
// admin.php – VisionPath Africa admin and junior admin dashboard
session_start();
require_once __DIR__ . '/config.php';

function ensure_admin_schema($conn) {
    $table_checks = [
        'schools' => "CREATE TABLE IF NOT EXISTS schools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_name VARCHAR(255) NOT NULL,
            county VARCHAR(100) NOT NULL,
            sub_county VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_school_name_county (school_name, county)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'classes' => "CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            school_id INT NOT NULL,
            class_name VARCHAR(100) NOT NULL,
            assigned_teacher_id INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_school_class (school_id, class_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'student_transfers' => "CREATE TABLE IF NOT EXISTS student_transfers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            from_school_id INT NOT NULL,
            to_school_id INT NOT NULL,
            requested_by_admin_id INT NOT NULL,
            approved_by_admin_id INT NULL,
            status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            request_reason TEXT NULL,
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($table_checks as $table => $sql) {
        try {
            $conn->query($sql);
        } catch (Exception $e) {
            error_log('Admin schema table check failed for ' . $table . ': ' . $e->getMessage());
        }
    }

    $column_checks = [
        'school_id' => "ALTER TABLE users ADD COLUMN school_id INT NULL AFTER role",
        'transfer_status' => "ALTER TABLE users ADD COLUMN transfer_status ENUM('normal', 'transfer_pending') NOT NULL DEFAULT 'normal' AFTER school_id",
        'class_id' => "ALTER TABLE users ADD COLUMN class_id INT NULL AFTER assigned_class"
    ];

    try {
        $class_check = $conn->query("SHOW COLUMNS FROM classes LIKE 'stream'");
        if ($class_check && $class_check->num_rows === 0) {
            $conn->query("ALTER TABLE classes ADD COLUMN stream VARCHAR(100) NULL AFTER class_name");
        }
    } catch (Exception $e) {
        error_log('Admin schema class stream check failed: ' . $e->getMessage());
    }

    foreach ($column_checks as $column => $alter_sql) {
        try {
            $check = $conn->query("SHOW COLUMNS FROM users LIKE '" . $column . "'");
            if ($check && $check->num_rows === 0) {
                $conn->query($alter_sql);
            }
        } catch (Exception $e) {
            error_log('Admin schema column check failed for ' . $column . ': ' . $e->getMessage());
        }
    }
}

ensure_admin_schema($conn);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$legacy_admin_password = 'MydearSisterCynthiaIloveYou';
$errors = [];
$messages = [];
$active_tab = $_GET['tab'] ?? 'overview';

function school_scope_sql($current_role, $school_id) {
    if ($current_role === 'junior_admin' && $school_id) {
        return ' AND school_id = ' . (int) $school_id;
    }
    return '';
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin.php');
    exit;
}

$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$current_role = $_SESSION['admin_role'] ?? 'guest';
$current_school_id = isset($_SESSION['school_id']) ? (int) $_SESSION['school_id'] : null;
$current_school_name = $_SESSION['school_name'] ?? 'School';
$current_admin_name = $_SESSION['admin_name'] ?? 'Administrator';

if (!$logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_email'])) {
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';

    if ($admin_email === '' || $admin_password === '') {
        $errors[] = 'Please enter both the admin email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password_hash, role, school_id FROM users WHERE email = ? AND (role IN ('super_admin', 'junior_admin') OR (role = '' AND assigned_class = 'Junior Admin')) LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $admin_email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($admin_password, $user['password_hash'])) {
                if (($user['role'] ?? '') === '') {
                    $repair = $conn->prepare('UPDATE users SET role = "junior_admin" WHERE id = ? LIMIT 1');
                    if ($repair) {
                        $repair->bind_param('i', $user['id']);
                        $repair->execute();
                        $repair->close();
                    }
                    $user['role'] = 'junior_admin';
                }

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = (int) $user['id'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_name'] = $user['name'];
                $_SESSION['school_id'] = isset($user['school_id']) ? (int) $user['school_id'] : null;
                $_SESSION['school_name'] = $user['school_name'] ?? 'School';
                $logged_in = true;
                $current_role = $user['role'];
                $current_school_id = isset($user['school_id']) ? (int) $user['school_id'] : null;
                $current_school_name = $_SESSION['school_name'];
                $current_admin_name = $user['name'];
                header('Location: admin.php');
                exit;
            }
        }

        if (strtolower($admin_email) === 'superadmin@visionpath.local' && $admin_password === $legacy_admin_password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = 0;
            $_SESSION['admin_role'] = 'super_admin';
            $_SESSION['admin_name'] = 'Super Admin';
            $_SESSION['school_id'] = null;
            $logged_in = true;
            $current_role = 'super_admin';
            $current_school_id = null;
            $current_admin_name = 'Super Admin';
            header('Location: admin.php');
            exit;
        }

        $errors[] = 'Invalid admin email or password.';
    }
}

if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_junior_admin'])) {
        if ($current_role !== 'super_admin') {
            $errors[] = 'Only the Super Admin can remove Junior Admins.';
        } else {
            $junior_admin_id = (int) ($_POST['delete_junior_admin'] ?? 0);

            if ($junior_admin_id <= 0) {
                $errors[] = 'Junior Admin ID is missing.';
            } else {
                $check_stmt = $conn->prepare('SELECT id, name, email FROM users WHERE id = ? AND role = "junior_admin" LIMIT 1');
                $check_stmt->bind_param('i', $junior_admin_id);
                $check_stmt->execute();
                $admin_record = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();

                if (!$admin_record) {
                    $errors[] = 'Junior Admin not found.';
                } else {
                    $delete_stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND role = "junior_admin" LIMIT 1');
                    $delete_stmt->bind_param('i', $junior_admin_id);
                    if ($delete_stmt->execute()) {
                        $messages[] = 'Junior Admin removed successfully.';
                    } else {
                        $errors[] = 'Could not remove the Junior Admin.';
                    }
                    $delete_stmt->close();
                }
            }
        }
    }

    if (isset($_POST['create_school'])) {
        if ($current_role !== 'super_admin') {
            $errors[] = 'Only the Super Admin can create a school.';
        } else {
            $school_name = trim($_POST['school_name'] ?? '');
            $county = trim($_POST['county'] ?? '');
            $sub_county = trim($_POST['sub_county'] ?? '');

            if ($school_name === '' || $county === '') {
                $errors[] = 'School name and county are required.';
            } else {
                $stmt = $conn->prepare('INSERT INTO schools (school_name, county, sub_county) VALUES (?, ?, ?)');
                if ($stmt) {
                    $stmt->bind_param('sss', $school_name, $county, $sub_county);
                    if ($stmt->execute()) {
                        $messages[] = 'New school created successfully.';
                    } else {
                        $errors[] = 'Failed to create the school.';
                    }
                    $stmt->close();
                }
            }
        }
    }

    if (isset($_POST['create_junior_admin'])) {
        if ($current_role !== 'super_admin') {
            $errors[] = 'Only the Super Admin can create Junior Admins.';
        } else {
            $full_name = trim($_POST['junior_admin_name'] ?? '');
            $email = trim($_POST['junior_admin_email'] ?? '');
            $password = $_POST['junior_admin_password'] ?? '';
            $school_id = (int) ($_POST['junior_admin_school_id'] ?? 0);

            if ($full_name === '' || $email === '' || $password === '' || $school_id <= 0) {
                $errors[] = 'Full name, email, temporary password, and school are required.';
            } else {
                $school_stmt = $conn->prepare('SELECT school_name FROM schools WHERE id = ? LIMIT 1');
                $school_stmt->bind_param('i', $school_id);
                $school_stmt->execute();
                $school_result = $school_stmt->get_result();
                $school = $school_result->fetch_assoc();
                $school_stmt->close();

                if (!$school) {
                    $errors[] = 'Selected school does not exist.';
                } else {
                    $dup = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $dup->bind_param('s', $email);
                    $dup->execute();
                    $dup->store_result();
                    if ($dup->num_rows > 0) {
                        $errors[] = 'A user with this email already exists.';
                    }
                    $dup->close();

                    if (empty($errors)) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $role = 'junior_admin';
                        $school_name_value = $school['school_name'];
                        $assigned_class = 'Junior Admin';
                        $insert_stmt = $conn->prepare('INSERT INTO users (name, email, password_hash, role, school_id, school_name, is_verified, assigned_class) VALUES (?, ?, ?, ?, ?, ?, 1, ?)');
                        $insert_stmt->bind_param('sssiiss', $full_name, $email, $password_hash, $role, $school_id, $school_name_value, $assigned_class);
                        if ($insert_stmt->execute()) {
                            $messages[] = 'Junior Admin created successfully for ' . htmlspecialchars($school_name_value, ENT_QUOTES, 'UTF-8') . '.';
                        } else {
                            $errors[] = 'Could not create the Junior Admin account.';
                        }
                        $insert_stmt->close();
                    }
                }
            }
        }
    }

    if (isset($_POST['create_class'])) {
        if ($current_role !== 'junior_admin') {
            $errors[] = 'Only the Junior Admin for the school can create classes.';
        } else {
            $class_name = trim($_POST['class_name'] ?? '');
            $stream_name = trim($_POST['stream_name'] ?? '');

            if ($class_name === '' || $stream_name === '') {
                $errors[] = 'Class name and stream are required.';
            } else {
                $dup = $conn->prepare('SELECT id FROM classes WHERE school_id = ? AND class_name = ? AND stream = ? LIMIT 1');
                $dup->bind_param('iss', $current_school_id, $class_name, $stream_name);
                $dup->execute();
                $dup->store_result();
                if ($dup->num_rows > 0) {
                    $errors[] = 'This class and stream already exists for your school.';
                }
                $dup->close();

                if (empty($errors)) {
                    $insert_class = $conn->prepare('INSERT INTO classes (school_id, class_name, stream, assigned_teacher_id) VALUES (?, ?, ?, NULL)');
                    $insert_class->bind_param('iss', $current_school_id, $class_name, $stream_name);
                    if ($insert_class->execute()) {
                        $messages[] = 'Class and stream added successfully.';
                    } else {
                        $errors[] = 'Could not add the class and stream.';
                    }
                    $insert_class->close();
                }
            }
        }
    }

    if (isset($_POST['create_teacher'])) {
        if ($current_role !== 'junior_admin') {
            $errors[] = 'Only the Junior Admin for the school can create teacher accounts.';
        } else {
            $teacher_name = trim($_POST['teacher_name'] ?? '');
            $teacher_email = trim($_POST['teacher_email'] ?? '');
            $teacher_password = $_POST['teacher_password'] ?? '';
            $teacher_class_id = (int) ($_POST['teacher_class_id'] ?? 0);

            if ($teacher_name === '' || $teacher_email === '' || $teacher_password === '' || $teacher_class_id <= 0) {
                $errors[] = 'Teacher name, email, password, and class are required.';
            } else {
                $class_stmt = $conn->prepare('SELECT id, class_name, stream, school_id, CONCAT_WS(" - ", class_name, stream) AS class_label FROM classes WHERE id = ? AND school_id = ? LIMIT 1');
                $class_stmt->bind_param('ii', $teacher_class_id, $current_school_id);
                $class_stmt->execute();
                $class_row = $class_stmt->get_result()->fetch_assoc();
                $class_stmt->close();

                if (!$class_row) {
                    $errors[] = 'The selected class does not belong to your school.';
                } else {
                    $dup = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                    $dup->bind_param('s', $teacher_email);
                    $dup->execute();
                    $dup->store_result();
                    if ($dup->num_rows > 0) {
                        $errors[] = 'A user with this email already exists.';
                    }
                    $dup->close();

                    if (empty($errors)) {
                        $password_hash = password_hash($teacher_password, PASSWORD_DEFAULT);
                        $teacher_role = 'teacher';
                        $teacher_school_name = $current_school_name ?? $_SESSION['school_name'] ?? (isset($current_school_id) ? $conn->query('SELECT school_name FROM schools WHERE id = ' . (int) $current_school_id . ' LIMIT 1')->fetch_assoc()['school_name'] ?? 'School' : 'School');
                        $teacher_class_name = $class_row['class_label'] ?? trim(($class_row['class_name'] ?? '') . ' - ' . ($class_row['stream'] ?? ''));
                        $insert_stmt = $conn->prepare('INSERT INTO users (name, email, password_hash, role, school_id, school_name, assigned_class, is_verified, class_id) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)');
                        $insert_stmt->bind_param('sssiissi', $teacher_name, $teacher_email, $password_hash, $teacher_role, $current_school_id, $teacher_school_name, $teacher_class_name, $teacher_class_id);
                        if ($insert_stmt->execute()) {
                            $teacher_user_id = $insert_stmt->insert_id;
                            $update_class = $conn->prepare('UPDATE classes SET assigned_teacher_id = ? WHERE id = ?');
                            $update_class->bind_param('ii', $teacher_user_id, $teacher_class_id);
                            $update_class->execute();
                            $update_class->close();
                            $messages[] = 'Teacher created and assigned to ' . htmlspecialchars($teacher_class_name, ENT_QUOTES, 'UTF-8') . ' successfully.';
                        } else {
                            $errors[] = 'Could not create the teacher account.';
                        }
                        $insert_stmt->close();
                    }
                }
            }
        }
    }

    if (isset($_POST['approve_teacher'])) {
        $teacher_id = (int) ($_POST['approve_teacher'] ?? 0);
        if ($teacher_id <= 0) {
            $errors[] = 'Teacher ID is missing.';
        } else {
            $allowed = false;
            $teacher_stmt = $conn->prepare('SELECT id, school_id FROM users WHERE id = ? AND role = ? LIMIT 1');
            if ($teacher_stmt) {
                $role = 'teacher';
                $teacher_stmt->bind_param('is', $teacher_id, $role);
                $teacher_stmt->execute();
                $teacher = $teacher_stmt->get_result()->fetch_assoc();
                $teacher_stmt->close();

                if ($teacher) {
                    if ($current_role === 'super_admin') {
                        $allowed = true;
                    } elseif ($current_role === 'junior_admin' && (int) $teacher['school_id'] === $current_school_id) {
                        $allowed = true;
                    }
                }
            }

            if ($allowed) {
                $stmt = $conn->prepare('UPDATE users SET is_verified = 1 WHERE id = ? AND role = ?');
                if ($stmt) {
                    $role = 'teacher';
                    $stmt->bind_param('is', $teacher_id, $role);
                    $stmt->execute();
                    $stmt->close();
                    $messages[] = 'Teacher account approved successfully.';
                }
            } else {
                $errors[] = 'You are not allowed to approve this teacher.';
            }
        }
    }

    if (isset($_POST['reassign_teacher'])) {
        $teacher_id = (int) ($_POST['teacher_id'] ?? 0);
        $class_id = (int) ($_POST['class_id'] ?? 0);

        if ($teacher_id <= 0 || $class_id <= 0) {
            $errors[] = 'Please choose a teacher and a class.';
        } else {
            $class_check = $conn->prepare('SELECT school_id, class_name FROM classes WHERE id = ? LIMIT 1');
            $class_check->bind_param('i', $class_id);
            $class_check->execute();
            $class_row = $class_check->get_result()->fetch_assoc();
            $class_check->close();

            if (!$class_row) {
                $errors[] = 'Selected class was not found.';
            } else {
                $teacher_check = $conn->prepare('SELECT id, school_id, role FROM users WHERE id = ? AND role = ? LIMIT 1');
                $teacher_role = 'teacher';
                $teacher_check->bind_param('is', $teacher_id, $teacher_role);
                $teacher_check->execute();
                $teacher_row = $teacher_check->get_result()->fetch_assoc();
                $teacher_check->close();

                if (!$teacher_row) {
                    $errors[] = 'Teacher not found.';
                } else {
                    $allowed = false;
                    if ($current_role === 'super_admin') {
                        $allowed = true;
                    } elseif ($current_role === 'junior_admin' && (int) $teacher_row['school_id'] === $current_school_id && (int) $class_row['school_id'] === $current_school_id) {
                        $allowed = true;
                    }

                    if (!$allowed) {
                        $errors[] = 'You cannot reassign this teacher outside your school scope.';
                    } else {
                        $update_user = $conn->prepare('UPDATE users SET assigned_class = ?, school_id = ? WHERE id = ? AND role = ?');
                        $update_user->bind_param('siis', $class_row['class_name'], $class_row['school_id'], $teacher_id, $teacher_role);
                        $update_user->execute();
                        $update_user->close();

                        $update_class = $conn->prepare('UPDATE classes SET assigned_teacher_id = ? WHERE id = ?');
                        $update_class->bind_param('ii', $teacher_id, $class_id);
                        $update_class->execute();
                        $update_class->close();

                        $messages[] = 'Teacher reassigned successfully.';
                    }
                }
            }
        }
    }

    if (isset($_POST['initiate_transfer'])) {
        if ($current_role !== 'junior_admin') {
            $errors[] = 'Only Junior Admins may initiate student transfers.';
        } else {
            $student_id = (int) ($_POST['student_id'] ?? 0);
            $target_school_id = (int) ($_POST['target_school_id'] ?? 0);
            $request_reason = trim($_POST['request_reason'] ?? '');

            if ($student_id <= 0 || $target_school_id <= 0) {
                $errors[] = 'A valid student and target school are required.';
            } elseif ($target_school_id === $current_school_id) {
                $errors[] = 'The destination school must be different from the current school.';
            } else {
                $student_stmt = $conn->prepare('SELECT id, school_id, transfer_status FROM users WHERE id = ? AND role = ? LIMIT 1');
                $student_role = 'student';
                $student_stmt->bind_param('is', $student_id, $student_role);
                $student_stmt->execute();
                $student_row = $student_stmt->get_result()->fetch_assoc();
                $student_stmt->close();

                if (!$student_row) {
                    $errors[] = 'Student not found.';
                } elseif ((int) $student_row['school_id'] !== $current_school_id) {
                    $errors[] = 'You can only transfer students from your own school.';
                } elseif (($student_row['transfer_status'] ?? 'normal') === 'transfer_pending') {
                    $errors[] = 'This student already has a pending transfer request.';
                } else {
                    $check_pending = $conn->prepare('SELECT id FROM student_transfers WHERE student_id = ? AND status = "pending" LIMIT 1');
                    $check_pending->bind_param('i', $student_id);
                    $check_pending->execute();
                    $check_pending->store_result();
                    if ($check_pending->num_rows > 0) {
                        $errors[] = 'A pending transfer already exists for this student.';
                    }
                    $check_pending->close();

                    if (empty($errors)) {
                        $ins = $conn->prepare('INSERT INTO student_transfers (student_id, from_school_id, to_school_id, requested_by_admin_id, status, request_reason) VALUES (?, ?, ?, ?, "pending", ?)');
                        if ($ins) {
                            $ins->bind_param('iiis', $student_id, $current_school_id, $target_school_id, $_SESSION['admin_user_id'], $request_reason);
                            if ($ins->execute()) {
                                $set_status = $conn->prepare('UPDATE users SET transfer_status = "transfer_pending" WHERE id = ? AND role = "student"');
                                $set_status->bind_param('i', $student_id);
                                $set_status->execute();
                                $set_status->close();
                                $messages[] = 'Transfer request created and the student is locked for edits at the source school.';
                            } else {
                                $errors[] = 'Unable to create transfer request.';
                            }
                            $ins->close();
                        }
                    }
                }
            }
        }
    }

    if (isset($_POST['approve_transfer'])) {
        $transfer_id = (int) ($_POST['transfer_id'] ?? 0);
        $class_id = (int) ($_POST['class_id'] ?? 0);

        if ($transfer_id <= 0 || $class_id <= 0) {
            $errors[] = 'A valid transfer and target class are required.';
        } else {
            $transfer_stmt = $conn->prepare('SELECT st.id, st.student_id, st.from_school_id, st.to_school_id, st.status, u.name AS student_name, u.transfer_status FROM student_transfers st LEFT JOIN users u ON u.id = st.student_id WHERE st.id = ? LIMIT 1');
            $transfer_stmt->bind_param('i', $transfer_id);
            $transfer_stmt->execute();
            $transfer = $transfer_stmt->get_result()->fetch_assoc();
            $transfer_stmt->close();

            if (!$transfer) {
                $errors[] = 'Transfer request not found.';
            } elseif (($transfer['status'] ?? 'pending') !== 'pending') {
                $errors[] = 'This request is not pending approval.';
            } elseif ($current_role !== 'junior_admin' || (int) $transfer['to_school_id'] !== $current_school_id) {
                $errors[] = 'You are not authorized to approve this incoming transfer.';
            } else {
                $class_stmt = $conn->prepare('SELECT id, school_id, class_name FROM classes WHERE id = ? AND school_id = ? LIMIT 1');
                $class_stmt->bind_param('ii', $class_id, $current_school_id);
                $class_stmt->execute();
                $class_row = $class_stmt->get_result()->fetch_assoc();
                $class_stmt->close();

                if (!$class_row) {
                    $errors[] = 'The selected class is not valid for your school.';
                } else {
                    $update_transfer = $conn->prepare('UPDATE student_transfers SET status = "approved", approved_by_admin_id = ?, processed_at = NOW() WHERE id = ?');
                    $update_transfer->bind_param('ii', $_SESSION['admin_user_id'], $transfer_id);
                    $update_transfer->execute();
                    $update_transfer->close();

                    $update_student = $conn->prepare('UPDATE users SET school_id = ?, class_id = ?, assigned_class = ?, transfer_status = "normal" WHERE id = ? AND role = "student"');
                    $update_student->bind_param('iisi', $current_school_id, $class_id, $class_row['class_name'], $transfer['student_id']);
                    $update_student->execute();
                    $update_student->close();

                    $messages[] = 'Transfer approved and student admission completed.';
                }
            }
        }
    }

    if (isset($_POST['reject_transfer'])) {
        $transfer_id = (int) ($_POST['transfer_id'] ?? 0);

        if ($transfer_id <= 0) {
            $errors[] = 'Transfer request ID is missing.';
        } else {
            $transfer_stmt = $conn->prepare('SELECT id, student_id, to_school_id, status FROM student_transfers WHERE id = ? LIMIT 1');
            $transfer_stmt->bind_param('i', $transfer_id);
            $transfer_stmt->execute();
            $transfer = $transfer_stmt->get_result()->fetch_assoc();
            $transfer_stmt->close();

            if (!$transfer) {
                $errors[] = 'Transfer request not found.';
            } elseif (($transfer['status'] ?? 'pending') !== 'pending') {
                $errors[] = 'Only pending transfers can be rejected.';
            } elseif ($current_role !== 'junior_admin' || (int) $transfer['to_school_id'] !== $current_school_id) {
                $errors[] = 'You are not authorized to reject this transfer.';
            } else {
                $update_transfer = $conn->prepare('UPDATE student_transfers SET status = "rejected", approved_by_admin_id = ?, processed_at = NOW() WHERE id = ?');
                $update_transfer->bind_param('ii', $_SESSION['admin_user_id'], $transfer_id);
                $update_transfer->execute();
                $update_transfer->close();

                $restore_student = $conn->prepare('UPDATE users SET transfer_status = "normal" WHERE id = ? AND role = "student"');
                $restore_student->bind_param('i', $transfer['student_id']);
                $restore_student->execute();
                $restore_student->close();

                $messages[] = 'Transfer rejected and student access restored under the source school.';
            }
        }
    }

    if (isset($_POST['toggle_mentor'])) {
        if ($current_role !== 'super_admin') {
            $errors[] = 'Only the Super Admin can manage mentor approvals.';
        } else {
            $toggle_id = (int) ($_POST['toggle_mentor'] ?? 0);
            $new_status = (int) ($_POST['new_status'] ?? 0);
            if ($toggle_id <= 0) {
                $errors[] = 'Mentor ID is missing.';
            } else {
                $stmt = $conn->prepare('UPDATE mentor_profiles SET is_verified_mentor = ?, is_approved = ? WHERE user_id = ?');
                if ($stmt) {
                    $stmt->bind_param('iii', $new_status, $new_status, $toggle_id);
                    $stmt->execute();
                    $stmt->close();
                    $messages[] = $new_status === 1 ? 'Mentor approved successfully.' : 'Mentor approval revoked.';
                } else {
                    $errors[] = 'Could not update mentor approval.';
                }
            }
        }
    }
}

$junior_admin_rows = [];
if ($logged_in && $current_role === 'super_admin') {
    $junior_admin_sql = 'SELECT u.id, u.name, u.email, u.school_id, s.school_name
        FROM users u
        LEFT JOIN schools s ON s.id = u.school_id
        WHERE u.role = "junior_admin"
        ORDER BY u.name ASC';
    $junior_admin_result = $conn->query($junior_admin_sql);
    if ($junior_admin_result) {
        $junior_admin_rows = $junior_admin_result->fetch_all(MYSQLI_ASSOC);
    }
}

$school_rows = [];
if ($logged_in) {
    $school_sql = 'SELECT s.id, s.school_name, s.county, s.sub_county,
        (SELECT u.name FROM users u WHERE u.role = "junior_admin" AND u.school_id = s.id ORDER BY u.id DESC LIMIT 1) AS junior_admin_name,
        (SELECT u.id FROM users u WHERE u.role = "junior_admin" AND u.school_id = s.id ORDER BY u.id DESC LIMIT 1) AS junior_admin_id,
        (SELECT COUNT(*) FROM users u2 WHERE u2.role = "teacher" AND u2.school_id = s.id) AS teacher_count,
        (SELECT COUNT(*) FROM users u3 WHERE u3.role = "student" AND u3.school_id = s.id) AS student_count,
        (SELECT COUNT(*) FROM classes c WHERE c.school_id = s.id) AS class_count
        FROM schools s';

    if ($current_role === 'junior_admin' && $current_school_id) {
        $school_sql .= ' WHERE s.id = ' . (int) $current_school_id;
    }
    $school_sql .= ' ORDER BY s.school_name ASC';

    $school_result = $conn->query($school_sql);
    if ($school_result) {
        $school_rows = $school_result->fetch_all(MYSQLI_ASSOC);
    }
}

$teacher_rows = [];
if ($logged_in) {
    $teacher_sql = 'SELECT u.id, u.name, u.email, u.assigned_class, u.is_verified, s.school_name, s.id as school_id
        FROM users u
        LEFT JOIN schools s ON s.id = u.school_id
        WHERE u.role = "teacher"';
    if ($current_role === 'junior_admin' && $current_school_id) {
        $teacher_sql .= ' AND u.school_id = ' . (int) $current_school_id;
    }
    $teacher_sql .= ' ORDER BY u.name ASC';

    $teacher_result = $conn->query($teacher_sql);
    if ($teacher_result) {
        $teacher_rows = $teacher_result->fetch_all(MYSQLI_ASSOC);
    }
}

$class_rows = [];
if ($logged_in) {
    $class_sql = 'SELECT c.id, c.school_id, c.class_name, c.stream, CONCAT_WS(" - ", c.class_name, c.stream) AS class_label, c.assigned_teacher_id, s.school_name,
        (SELECT u.name FROM users u WHERE u.id = c.assigned_teacher_id LIMIT 1) AS teacher_name
        FROM classes c
        LEFT JOIN schools s ON s.id = c.school_id';
    if ($current_role === 'junior_admin' && $current_school_id) {
        $class_sql .= ' WHERE c.school_id = ' . (int) $current_school_id;
    }
    $class_sql .= ' ORDER BY s.school_name ASC, c.class_name ASC, c.stream ASC';

    $class_result = $conn->query($class_sql);
    if ($class_result) {
        $class_rows = $class_result->fetch_all(MYSQLI_ASSOC);
    }
}

$student_rows = [];
if ($logged_in) {
    $student_sql = 'SELECT u.id, u.name, u.email, u.school_id, u.assigned_class, u.transfer_status, u.class_id, s.school_name,
        c.class_name AS class_name,
        (SELECT COUNT(*) FROM projects p WHERE p.student_id = u.id) AS project_count
        FROM users u
        LEFT JOIN schools s ON s.id = u.school_id
        LEFT JOIN classes c ON c.id = u.class_id
        WHERE u.role = "student"';

    if ($current_role === 'junior_admin' && $current_school_id) {
        $student_sql .= ' AND u.school_id = ' . (int) $current_school_id;
    }
    $student_sql .= ' ORDER BY u.name ASC';

    $student_result = $conn->query($student_sql);
    if ($student_result) {
        $student_rows = $student_result->fetch_all(MYSQLI_ASSOC);
    }
}

$transfer_rows = [];
if ($logged_in) {
    $transfer_sql = 'SELECT st.id, st.student_id, st.from_school_id, st.to_school_id, st.requested_by_admin_id, st.approved_by_admin_id,
        st.status, st.request_reason, st.requested_at, st.processed_at,
        student.name AS student_name,
        student.email AS student_email,
        from_school.school_name AS from_school_name,
        to_school.school_name AS to_school_name,
        req_admin.name AS request_admin_name,
        app_admin.name AS approved_admin_name
        FROM student_transfers st
        INNER JOIN users student ON student.id = st.student_id
        INNER JOIN schools from_school ON from_school.id = st.from_school_id
        INNER JOIN schools to_school ON to_school.id = st.to_school_id
        LEFT JOIN users req_admin ON req_admin.id = st.requested_by_admin_id
        LEFT JOIN users app_admin ON app_admin.id = st.approved_by_admin_id';

    if ($current_role === 'junior_admin' && $current_school_id) {
        $transfer_sql .= ' WHERE (st.from_school_id = ' . (int) $current_school_id . ' OR st.to_school_id = ' . (int) $current_school_id . ')';
    }
    $transfer_sql .= ' ORDER BY st.requested_at DESC';

    $transfer_result = $conn->query($transfer_sql);
    if ($transfer_result) {
        $transfer_rows = $transfer_result->fetch_all(MYSQLI_ASSOC);
    }
}

$overview = [
    'schools' => 0,
    'junior_admins' => 0,
    'teachers' => 0,
    'active_students' => 0,
];

if ($logged_in) {
    if ($current_role === 'junior_admin' && $current_school_id) {
        $overview['schools'] = 1;
        $overview['junior_admins'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "junior_admin" AND school_id = ' . (int) $current_school_id)->fetch_assoc()['c'];
        $overview['teachers'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "teacher" AND school_id = ' . (int) $current_school_id)->fetch_assoc()['c'];
        $overview['active_students'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "student" AND school_id = ' . (int) $current_school_id)->fetch_assoc()['c'];
    } else {
        $overview['schools'] = (int) $conn->query('SELECT COUNT(*) as c FROM schools')->fetch_assoc()['c'];
        $overview['junior_admins'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "junior_admin"')->fetch_assoc()['c'];
        $overview['teachers'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "teacher"' . school_scope_sql($current_role, $current_school_id))->fetch_assoc()['c'];
        $overview['active_students'] = (int) $conn->query('SELECT COUNT(*) as c FROM users WHERE role = "student"' . school_scope_sql($current_role, $current_school_id))->fetch_assoc()['c'];
    }
}

if ($logged_in && $current_role === 'super_admin') {
    $mentor_sql = 'SELECT u.id, u.name, u.email, mp.full_name, mp.institution_company, mp.job_title, mp.cbc_pathway_interest,
        mp.linkedin_url, mp.mentorship_capacity, mp.professional_bio,
        COALESCE(mp.is_approved, mp.is_verified_mentor, 0) AS is_approved,
        COALESCE(mp.is_verified_mentor, mp.is_approved, 0) AS is_verified_mentor
        FROM users u
        INNER JOIN mentor_profiles mp ON mp.user_id = u.id
        ORDER BY COALESCE(mp.is_approved, mp.is_verified_mentor, 0) ASC, u.id DESC';
    $mentor_result = $conn->query($mentor_sql);
    $mentors = $mentor_result ? $mentor_result->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $mentors = [];
}

if (!$logged_in) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VisionPath Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1d4ed8;
            --success: #15803d;
            --danger: #b91c1c;
            --warning: #b45309;
            --bg: #f3f6fb;
            --card: #ffffff;
            --muted: #64748b;
            --line: #e2e8f0;
            --text: #0f172a;
            --shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        a { text-decoration: none; }
        .topbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 18px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
        }
        .topbar h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .topbar .user-box {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .topbar .user-box .pill {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 6px 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .topbar .logout {
            color: white;
            font-weight: 600;
            opacity: 0.9;
        }
        .wrapper {
            max-width: 1280px;
            margin: 28px auto;
            padding: 0 20px 40px;
        }
        .login-box {
            max-width: 420px;
            margin: 80px auto;
            background: var(--card);
            padding: 28px;
            border-radius: 16px;
            box-shadow: var(--shadow);
        }
        .login-box h2 {
            margin: 0 0 10px;
            font-size: 26px;
        }
        .login-box p {
            color: var(--muted);
            margin: 0 0 18px;
            font-size: 14px;
        }
        .login-box input, .login-box select, .form-card input, .form-card select, .table-card input, .table-card select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: white;
            font-size: 14px;
            margin-bottom: 12px;
            color: var(--text);
        }
        .login-box button, .primary-btn, .danger-btn, .secondary-btn {
            border: none;
            border-radius: 8px;
            padding: 11px 14px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s ease-in-out;
        }
        .login-box button, .primary-btn {
            background: var(--primary);
            color: white;
            width: 100%;
        }
        .primary-btn:hover { background: var(--primary-dark); }
        .secondary-btn {
            background: #eef2ff;
            color: var(--primary);
        }
        .danger-btn {
            background: var(--danger);
            color: white;
        }
        .card-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .metric-card {
            background: white;
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 18px 18px 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .metric-card .label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .metric-card .value {
            margin-top: 10px;
            font-size: 30px;
            font-weight: 800;
            line-height: 1;
        }
        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 24px 0 20px;
        }
        .tab-btn {
            padding: 10px 16px;
            border-radius: 10px;
            background: white;
            border: 1px solid var(--line);
            color: var(--muted);
            font-weight: 600;
            display: inline-block;
        }
        .tab-btn.active {
            background: #dbeafe;
            border-color: #bfdbfe;
            color: var(--primary);
        }
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert.success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .alert.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .form-card, .table-card {
            background: white;
            border-radius: 14px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(148, 163, 184, 0.08);
            padding: 18px;
            margin-bottom: 20px;
        }
        .form-card h3, .table-card h3 {
            margin: 0 0 12px;
            font-size: 18px;
        }
        .inline-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            align-items: end;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            font-size: 14px;
        }
        th {
            background: #f8fafc;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge.pending { background: #fef3c7; color: var(--warning); }
        .badge.approved { background: #dcfce7; color: var(--success); }
        .muted { color: var(--muted); }
        .section-header { display:flex; justify-content:space-between; align-items:center; gap:10px; margin:10px 0 12px; }
        .small-note { font-size: 12px; color: var(--muted); }
        @media (max-width: 700px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 10px; }
            .tabs { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <?php if (!$logged_in): ?>
        <div class="topbar">
            <h1>VisionPath Africa Admin</h1>
        </div>
        <div class="wrapper">
            <div class="login-box">
                <h2>Admin Login</h2>
                <p>Sign in as the Super Admin or a Junior Admin.</p>
                <?php if (!empty($errors)): ?>
                    <div class="alert error"><?php echo htmlspecialchars(implode('<br>', $errors), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form method="POST" action="admin.php">
                    <input type="email" name="admin_email" placeholder="Email address" required>
                    <input type="password" name="admin_password" placeholder="Password" required>
                    <button type="submit">Access Dashboard</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="topbar">
            <h1>VisionPath Africa Admin</h1>
            <div class="user-box">
                <span class="pill"><?php echo htmlspecialchars(str_replace('_', ' ', strtoupper($current_role))); ?></span>
                <span><?php echo htmlspecialchars($current_admin_name); ?></span>
                <a class="logout" href="?logout=1">Logout</a>
            </div>
        </div>

        <div class="wrapper">
            <?php if (!empty($errors)): ?>
                <div class="alert error"><?php echo htmlspecialchars(implode('<br>', $errors), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($messages)): ?>
                <div class="alert success"><?php echo htmlspecialchars(implode('<br>', $messages), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="tabs">
                <a class="tab-btn <?php echo ($active_tab === 'overview') ? 'active' : ''; ?>" href="admin.php?tab=overview">Overview</a>
                <a class="tab-btn <?php echo ($active_tab === 'schools') ? 'active' : ''; ?>" href="admin.php?tab=schools">Schools &amp; Junior Admins</a>
                <a class="tab-btn <?php echo ($active_tab === 'teachers') ? 'active' : ''; ?>" href="admin.php?tab=teachers">Teacher Approvals</a>
                <a class="tab-btn <?php echo ($active_tab === 'students') ? 'active' : ''; ?>" href="admin.php?tab=students">Student Profiles</a>
                <a class="tab-btn <?php echo ($active_tab === 'transfers') ? 'active' : ''; ?>" href="admin.php?tab=transfers">Transfer Management</a>
                <?php if ($current_role === 'super_admin'): ?>
                    <a class="tab-btn <?php echo ($active_tab === 'mentors') ? 'active' : ''; ?>" href="admin.php?tab=mentors">Mentors</a>
                <?php endif; ?>
            </div>

            <?php if ($active_tab === 'overview'): ?>
                <div class="card-row">
                    <?php if ($current_role === 'super_admin'): ?>
                        <div class="metric-card">
                            <div class="label">Total Schools</div>
                            <div class="value"><?php echo (int) $overview['schools']; ?></div>
                        </div>
                        <div class="metric-card">
                            <div class="label">Junior Admins</div>
                            <div class="value"><?php echo (int) $overview['junior_admins']; ?></div>
                        </div>
                    <?php else: ?>
                        <div class="metric-card">
                            <div class="label">Assigned School</div>
                            <div class="value">1</div>
                        </div>
                        <div class="metric-card">
                            <div class="label">Junior Admins in School</div>
                            <div class="value"><?php echo (int) $overview['junior_admins']; ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="metric-card">
                        <div class="label">Teachers</div>
                        <div class="value"><?php echo (int) $overview['teachers']; ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="label">Active Students</div>
                        <div class="value"><?php echo (int) $overview['active_students']; ?></div>
                    </div>
                </div>

                <div class="table-card">
                    <div class="section-header">
                        <h3>School Snapshot</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>School</th>
                                <th>County</th>
                                <th>Junior Admin</th>
                                <th>Classes</th>
                                <th>Teachers</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($school_rows)): ?>
                                <tr><td colspan="6" class="muted">No school data found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($school_rows as $school): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($school['county']); ?></td>
                                        <td><?php echo htmlspecialchars($school['junior_admin_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo (int) ($school['class_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($school['teacher_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($school['student_count'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'schools'): ?>
                <?php if ($current_role === 'super_admin'): ?>
                    <div class="form-card">
                        <h3>Create New School</h3>
                        <form method="POST" action="admin.php?tab=schools">
                            <div class="inline-form">
                                <div>
                                    <input type="text" name="school_name" placeholder="School name" required>
                                </div>
                                <div>
                                    <input type="text" name="county" placeholder="County" required>
                                </div>
                                <div>
                                    <input type="text" name="sub_county" placeholder="Sub-county (optional)">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="primary-btn" name="create_school" value="1">+ Add New School</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($current_role === 'super_admin'): ?>
                    <div class="form-card">
                        <h3>Create Junior Admin</h3>
                        <form method="POST" action="admin.php?tab=schools">
                            <div class="inline-form">
                                <div>
                                    <input type="text" name="junior_admin_name" placeholder="Full name" required>
                                </div>
                                <div>
                                    <input type="email" name="junior_admin_email" placeholder="Email address" required>
                                </div>
                                <div>
                                    <input type="text" name="junior_admin_password" placeholder="Temporary password" required>
                                </div>
                                <div>
                                    <select name="junior_admin_school_id" required>
                                        <option value="">Select school</option>
                                        <?php foreach ($school_rows as $school): ?>
                                            <option value="<?php echo (int) $school['id']; ?>"><?php echo htmlspecialchars($school['school_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="primary-btn" name="create_junior_admin" value="1">Create Junior Admin</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($current_role === 'super_admin'): ?>
                    <div class="table-card">
                        <div class="section-header">
                            <h3>Junior Admin Accounts</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Assigned School</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($junior_admin_rows)): ?>
                                    <tr><td colspan="4" class="muted">No junior admin accounts found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($junior_admin_rows as $junior_admin): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($junior_admin['name']); ?></td>
                                            <td><?php echo htmlspecialchars($junior_admin['email']); ?></td>
                                            <td><?php echo htmlspecialchars($junior_admin['school_name'] ?? 'Unassigned'); ?></td>
                                            <td>
                                                <form method="POST" action="admin.php?tab=schools" onsubmit="return confirm('Remove this junior admin?');">
                                                    <input type="hidden" name="delete_junior_admin" value="<?php echo (int) $junior_admin['id']; ?>">
                                                    <button type="submit" class="danger-btn">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="table-card">
                    <div class="section-header">
                        <h3>Schools and Junior Admin Assignments</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>School</th>
                                <th>County</th>
                                <th>Junior Admin</th>
                                <th>Classes</th>
                                <th>Teachers</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($school_rows)): ?>
                                <tr><td colspan="6" class="muted">No schools available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($school_rows as $school): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($school['county']); ?></td>
                                        <td>
                                            <?php if (!empty($school['junior_admin_name'])): ?>
                                                <?php echo htmlspecialchars($school['junior_admin_name']); ?>
                                            <?php else: ?>
                                                <span class="muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (int) ($school['class_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($school['teacher_count'] ?? 0); ?></td>
                                        <td><?php echo (int) ($school['student_count'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'teachers'): ?>
                <?php if ($current_role === 'junior_admin'): ?>
                    <div class="form-card">
                        <h3>Add Class and Stream</h3>
                        <form method="POST" action="admin.php?tab=teachers">
                            <div class="inline-form">
                                <div>
                                    <input type="text" name="class_name" placeholder="Class name e.g. Form 1" required>
                                </div>
                                <div>
                                    <input type="text" name="stream_name" placeholder="Stream e.g. East / Science / Red" required>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="primary-btn" name="create_class" value="1">Add Class Stream</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="form-card">
                        <h3>Create Teacher for This School</h3>
                        <form method="POST" action="admin.php?tab=teachers">
                            <div class="inline-form">
                                <div>
                                    <input type="text" name="teacher_name" placeholder="Teacher full name" required>
                                </div>
                                <div>
                                    <input type="email" name="teacher_email" placeholder="Teacher email" required>
                                </div>
                                <div>
                                    <input type="text" name="teacher_password" placeholder="Temporary password" required>
                                </div>
                                <div>
                                    <select name="teacher_class_id" required>
                                        <option value="">Select class stream</option>
                                        <?php foreach ($class_rows as $class): ?>
                                            <?php if ((int) $class['school_id'] === (int) $current_school_id): ?>
                                                <option value="<?php echo (int) $class['id']; ?>"><?php echo htmlspecialchars($class['class_label'] ?? ($class['class_name'] . (empty($class['stream']) ? '' : ' - ' . $class['stream']))); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="primary-btn" name="create_teacher" value="1">Create Teacher</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="table-card">
                    <div class="section-header">
                        <h3>Teacher Approvals</h3>
                        <span class="small-note"><?php echo $current_role === 'super_admin' ? 'Global view' : 'School-scoped view'; ?></span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>School</th>
                                <th>Assigned Class</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teacher_rows)): ?>
                                <tr><td colspan="6" class="muted">No teacher registrations found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($teacher_rows as $teacher): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['school_name'] ?? 'Unassigned'); ?></td>
                                        <td>
                                            <?php if (!empty($teacher['assigned_class'])): ?>
                                                <?php echo htmlspecialchars($teacher['assigned_class']); ?>
                                            <?php else: ?>
                                                <span class="muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $teacher['is_verified'] === 1): ?>
                                                <span class="badge approved">Approved</span>
                                            <?php else: ?>
                                                <span class="badge pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $teacher['is_verified'] !== 1): ?>
                                                <form method="POST" action="admin.php?tab=teachers" style="display:inline-block;">
                                                    <button type="submit" class="primary-btn" name="approve_teacher" value="<?php echo (int) $teacher['id']; ?>">Approve</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">Approved</span>
                                            <?php endif; ?>

                                            <?php if (!empty($class_rows)): ?>
                                                <form method="POST" action="admin.php?tab=teachers" style="margin-top:8px;">
                                                    <input type="hidden" name="teacher_id" value="<?php echo (int) $teacher['id']; ?>">
                                                    <select name="class_id" required>
                                                        <option value="">Move to class</option>
                                                        <?php foreach ($class_rows as $class): ?>
                                                            <?php if (($current_role === 'super_admin' || (int) $class['school_id'] === (int) $teacher['school_id']) && (int)$class['school_id'] === (int)($teacher['school_id'] ?? 0)): ?>
                                                                <option value="<?php echo (int) $class['id']; ?>"><?php echo htmlspecialchars($class['class_label'] ?? ($class['class_name'] . (empty($class['stream']) ? '' : ' - ' . $class['stream']))); ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="secondary-btn" name="reassign_teacher" value="1">Reassign</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'students'): ?>
                <div class="table-card">
                    <div class="section-header">
                        <h3>Student Profiles</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Portfolio</th>
                                <th>Initiate Transfer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($student_rows)): ?>
                                <tr><td colspan="6" class="muted">No student profiles found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($student_rows as $student): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($student['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['school_name'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name'] ?? $student['assigned_class'] ?? 'Unassigned'); ?></td>
                                        <td>
                                            <?php if (($student['transfer_status'] ?? 'normal') === 'transfer_pending'): ?>
                                                <span class="badge pending">Transfer Pending</span>
                                            <?php else: ?>
                                                <span class="badge approved">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="portfolio.php?student_id=<?php echo (int) $student['id']; ?>" target="_blank" class="secondary-btn" style="display:inline-block; text-align:center;">View Portfolio</a>
                                        </td>
                                        <td>
                                            <?php if (($student['transfer_status'] ?? 'normal') !== 'transfer_pending' && ($current_role === 'junior_admin' || $current_role === 'super_admin')): ?>
                                                <?php if (!empty($school_rows)): ?>
                                                    <form method="POST" action="admin.php?tab=students">
                                                        <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                                                        <select name="target_school_id" required>
                                                            <option value="">Select destination school</option>
                                                            <?php foreach ($school_rows as $school): ?>
                                                                <?php if ((int) $school['id'] !== (int) ($student['school_id'] ?? 0)): ?>
                                                                    <option value="<?php echo (int) $school['id']; ?>"><?php echo htmlspecialchars($school['school_name']); ?></option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <textarea name="request_reason" rows="2" placeholder="Transfer reason (optional)"></textarea>
                                                        <button type="submit" class="primary-btn" name="initiate_transfer" value="1">Initiate Transfer</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="muted">No destination school available</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="muted">Locked by transfer</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'transfers'): ?>
                <div class="table-card">
                    <div class="section-header">
                        <h3>Outbound Requests</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $outbound = array_values(array_filter($transfer_rows, function($row) use ($current_role, $current_school_id) {
                                    if ($current_role === 'junior_admin' && $current_school_id) {
                                        return (int) $row['from_school_id'] === (int) $current_school_id;
                                    }
                                    return true;
                                }));
                            ?>
                            <?php if (empty($outbound)): ?>
                                <tr><td colspan="5" class="muted">No outbound transfer requests.</td></tr>
                            <?php else: ?>
                                <?php foreach ($outbound as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($t['from_school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($t['to_school_name']); ?></td>
                                        <td><?php echo htmlspecialchars($t['request_reason'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($t['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-card">
                    <div class="section-header">
                        <h3>Inbound Requests</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Former School</th>
                                <th>Portfolio</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $inbound = array_values(array_filter($transfer_rows, function($row) use ($current_role, $current_school_id) {
                                    if ($current_role === 'junior_admin' && $current_school_id) {
                                        return (int) $row['to_school_id'] === (int) $current_school_id && $row['status'] === 'pending';
                                    }
                                    return $row['status'] === 'pending';
                                }));
                            ?>
                            <?php if (empty($inbound)): ?>
                                <tr><td colspan="5" class="muted">No incoming transfer requests require approval.</td></tr>
                            <?php else: ?>
                                <?php foreach ($inbound as $t): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($t['student_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($t['student_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($t['from_school_name']); ?></td>
                                        <td><a href="portfolio.php?student_id=<?php echo (int) $t['student_id']; ?>" target="_blank">View Portfolio</a></td>
                                        <td><?php echo htmlspecialchars($t['request_reason'] ?: '—'); ?></td>
                                        <td>
                                            <form method="POST" action="admin.php?tab=transfers">
                                                <input type="hidden" name="transfer_id" value="<?php echo (int) $t['id']; ?>">
                                                <select name="class_id" required>
                                                    <option value="">Choose class</option>
                                                    <?php foreach ($class_rows as $class): ?>
                                                        <?php if ((int) $class['school_id'] === (int) $current_school_id || $current_role === 'super_admin'): ?>
                                                            <option value="<?php echo (int) $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="primary-btn" name="approve_transfer" value="1">Approve &amp; Complete Admission</button>
                                            </form>
                                            <form method="POST" action="admin.php?tab=transfers" style="margin-top:8px;">
                                                <input type="hidden" name="transfer_id" value="<?php echo (int) $t['id']; ?>">
                                                <button type="submit" class="danger-btn" name="reject_transfer" value="1">Reject Transfer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($active_tab === 'mentors' && $current_role === 'super_admin'): ?>
                <div class="table-card">
                    <div class="section-header">
                        <h3>Mentor Matching Overview</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Mentor</th>
                                <th>Institution</th>
                                <th>Role</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mentors)): ?>
                                <tr><td colspan="6" class="muted">No mentor profiles found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($mentors as $mentor): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($mentor['full_name']); ?></strong><br>
                                            <span class="muted"><?php echo htmlspecialchars($mentor['email']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($mentor['institution_company']); ?></td>
                                        <td><?php echo htmlspecialchars($mentor['job_title']); ?></td>
                                        <td><?php echo (int) $mentor['mentorship_capacity']; ?></td>
                                        <td>
                                            <?php if (!empty($mentor['is_approved']) || !empty($mentor['is_verified_mentor'])): ?>
                                                <span class="badge approved">Approved</span>
                                            <?php else: ?>
                                                <span class="badge pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($mentor['is_approved']) && empty($mentor['is_verified_mentor'])): ?>
                                                <form method="POST" action="admin.php?tab=mentors">
                                                    <button type="submit" class="primary-btn" name="toggle_mentor" value="<?php echo (int) $mentor['id']; ?>">Approve</button>
                                                    <input type="hidden" name="new_status" value="1">
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="admin.php?tab=mentors" onsubmit="return confirm('Revoke mentor approval?');">
                                                    <button type="submit" class="danger-btn" name="toggle_mentor" value="<?php echo (int) $mentor['id']; ?>">Revoke</button>
                                                    <input type="hidden" name="new_status" value="0">
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>
<?php if ($logged_in) { $conn->close(); } ?>
