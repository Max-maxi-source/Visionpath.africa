<?php
// admin.php – VisionPath Mentor Approval Panel
session_start();
require_once 'config.php';

// Show all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ADMIN_PASSWORD', 'MydearSisterCynthiaIloveYou');

$admin_authed = isset($_SESSION['admin_authed']) && $_SESSION['admin_authed'] === true;
$auth_error   = '';
$action_msg   = '';
$db_error     = '';

// ── 1. Logout ───────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_authed']);
    header("Location: admin.php");
    exit;
}

// ── 2. Admin login ───────────────────────────────────────────────────────────
if (!$admin_authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_authed'] = true;
        $admin_authed = true;
    } else {
        $auth_error = "Incorrect admin password.";
    }
}

// ── 3. Approve / Revoke mentor ───────────────────────────────────────────────
if ($admin_authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_mentor'])) {
    $toggle_id  = intval($_POST['toggle_mentor']);
    $new_status = intval($_POST['new_status']);

    $stmt_tog = $conn->prepare("UPDATE mentor_profiles SET is_verified_mentor = ?, is_approved = ? WHERE user_id = ?");
    if ($stmt_tog) {
        $stmt_tog->bind_param("iii", $new_status, $new_status, $toggle_id);
        $stmt_tog->execute();
        $stmt_tog->close();
        $action_msg = $new_status === 1 ? "Mentor approved successfully." : "Mentor approval revoked.";
    } else {
        $db_error = "DB Error on toggle: " . $conn->error;
    }
}

// ── 4. Check mentor_profiles table exists, then fetch ────────────────────────
$mentors     = [];
$table_exists = false;

if ($admin_authed) {
    // Check if the table exists before querying
    $tcheck = $conn->query("SHOW TABLES LIKE 'mentor_profiles'");
    if ($tcheck && $tcheck->num_rows > 0) {
        $table_exists = true;
        $res = $conn->query(
            "SELECT u.id, u.email, mp.full_name, mp.institution_company,
                    mp.job_title, mp.cbc_pathway_interest, mp.linkedin_url,
                    mp.mentorship_capacity, mp.professional_bio,
                    COALESCE(mp.is_approved, mp.is_verified_mentor, 0) AS is_approved,
                    COALESCE(mp.is_verified_mentor, mp.is_approved, 0) AS is_verified_mentor
             FROM users u
             INNER JOIN mentor_profiles mp ON mp.user_id = u.id
             ORDER BY COALESCE(mp.is_approved, mp.is_verified_mentor, 0) ASC, u.id DESC"
        );
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $mentors[] = $row;
            }
        } else {
            $db_error = "Query error: " . $conn->error;
        }
    } else {
        $db_error = "The <strong>mentor_profiles</strong> table does not exist yet. Please run 
            <a href='update_mentor_db.php' style='color:#1e40af;font-weight:bold;'>update_mentor_db.php</a> first.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – VisionPath</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af; --danger: #dc2626; --success: #16a34a;
            --bg: #f1f5f9; --card: #fff; --border: #e2e8f0; --muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: #1e293b; min-height: 100vh; }

        .navbar {
            background: var(--primary); color: #fff;
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(30,64,175,0.3);
        }
        .navbar h1 { font-size: 18px; font-weight: 700; }
        .navbar a { color: rgba(255,255,255,0.85); text-decoration: none; font-size: 14px; font-weight: 500; }
        .navbar a:hover { color: #fff; }
        .wrapper { max-width: 1150px; margin: 30px auto; padding: 0 20px; }

        /* Login */
        .login-card {
            max-width: 380px; margin: 80px auto; background: var(--card);
            padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .login-card h2 { margin-bottom: 6px; font-size: 22px; }
        .login-card p { color: var(--muted); font-size: 14px; margin-bottom: 20px; }
        .login-card input {
            width: 100%; padding: 11px 12px; border: 1px solid var(--border);
            border-radius: 7px; font-size: 15px; margin-bottom: 12px;
            font-family: inherit; transition: border-color 0.2s;
        }
        .login-card input:focus { border-color: var(--primary); outline: none; }
        .login-card button {
            width: 100%; padding: 12px; background: var(--primary); color: #fff;
            border: none; border-radius: 7px; cursor: pointer; font-size: 15px; font-weight: 600;
            transition: background 0.2s;
        }
        .login-card button:hover { background: #1d4ed8; }

        /* Alerts */
        .alert { padding: 13px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-danger  { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert-warning { background: #fffbeb; color: #78350f; border: 1px solid #fde68a; }
        .error-msg { color: var(--danger); font-size: 14px; margin-bottom: 14px; }

        /* Table */
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
        .card {
            background: var(--card); border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06); overflow: auto; margin-bottom: 30px;
        }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 13px 16px; text-align: left; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
        th { background: #f8fafc; color: var(--muted); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.6px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }

        .btn { padding: 7px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-approve { background: var(--success); color: #fff; }
        .btn-approve:hover { background: #15803d; }
        .btn-revoke  { background: var(--danger); color: #fff; }
        .btn-revoke:hover  { background: #b91c1c; }

        .meta { font-size: 13px; color: var(--muted); }
        .empty { text-align: center; padding: 50px; color: var(--muted); font-size: 15px; }

        .bio-row td { background: #f8fafc !important; font-size: 13px; color: var(--muted); padding: 6px 16px 14px !important; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>⚙️ VisionPath Admin</h1>
    <?php if ($admin_authed): ?>
        <a href="?logout=1">Logout</a>
    <?php endif; ?>
</nav>

<?php if (!$admin_authed): ?>
    <!-- ── Login Gate ─────────────────────────────── -->
    <div class="login-card">
        <h2>Admin Login</h2>
        <p>Enter the admin password to manage mentor approvals.</p>
        <?php if ($auth_error): ?>
            <div class="error-msg">❌ <?php echo htmlspecialchars($auth_error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="password" name="admin_password" placeholder="Admin password" required autofocus>
            <button type="submit">Access Panel</button>
        </form>
    </div>

<?php else: ?>
    <!-- ── Admin Dashboard ───────────────────────── -->
    <div class="wrapper">

        <?php if ($action_msg): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($action_msg); ?></div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="alert alert-warning">⚠️ <?php echo $db_error; ?></div>
        <?php endif; ?>

        <div class="section-title">Mentor Verification Queue
            <span style="font-size:14px;font-weight:400;color:var(--muted);margin-left:10px;">
                <?php echo count($mentors); ?> mentor(s) registered
            </span>
        </div>

        <div class="card">
            <?php if (count($mentors) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name &amp; Institution</th>
                            <th>Job Title</th>
                            <th>Pathway</th>
                            <th>Cap.</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mentors as $m): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['full_name']); ?></strong><br>
                                    <span class="meta"><?php echo htmlspecialchars($m['institution_company']); ?></span>
                                    <?php if (!empty($m['linkedin_url'])): ?>
                                        <br><a href="<?php echo htmlspecialchars($m['linkedin_url']); ?>"
                                               target="_blank"
                                               style="font-size:12px;color:#1e40af;">LinkedIn ↗</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($m['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($m['cbc_pathway_interest']); ?></td>
                                <td><?php echo intval($m['mentorship_capacity']); ?></td>
                                <td class="meta"><?php echo htmlspecialchars($m['email']); ?></td>
                                <td>
                                    <?php if (!empty($m['is_approved']) || !empty($m['is_verified_mentor'])): ?>
                                        <span class="badge badge-approved">✅ Approved</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">⏳ Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (empty($m['is_approved']) && empty($m['is_verified_mentor'])): ?>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="toggle_mentor" value="<?php echo intval($m['id']); ?>">
                                            <input type="hidden" name="new_status" value="1">
                                            <button type="submit" class="btn btn-approve">Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="" style="display:inline;"
                                              onsubmit="return confirm('Revoke this mentor\'s approval?');">
                                            <input type="hidden" name="toggle_mentor" value="<?php echo intval($m['id']); ?>">
                                            <input type="hidden" name="new_status" value="0">
                                            <button type="submit" class="btn btn-revoke">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($m['professional_bio'])): ?>
                                <tr class="bio-row">
                                    <td colspan="7">
                                        <strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($m['professional_bio'])); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (!$db_error): ?>
                <div class="empty">No mentor registrations found yet.</div>
            <?php endif; ?>
        </div>

        <div style="text-align:center; margin-top:10px;">
            <a href="index.php" style="color:var(--primary);font-size:14px;text-decoration:none;">← Back to Home</a>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>
