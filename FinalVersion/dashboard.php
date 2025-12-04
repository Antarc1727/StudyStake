<?php
ob_start();

session_start();
include 'db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    $del_stmt = $conn->prepare("
        DELETE FROM target_buffer
        WHERE id = ? AND user_id = ?
    ");
    if ($del_stmt) {
        $del_stmt->bind_param("ii", $delete_id, $user_id);
        if ($del_stmt->execute()) {
            $del_stmt->close();
            header("Location: dashboard.php?msg=deleted");
            exit();
        }
        $del_stmt->close();
    }
}

if (isset($_GET['delete_target'])) {
    $delete_target_id = intval($_GET['delete_target']);

    $del_t_stmt = $conn->prepare("
        DELETE FROM targets
        WHERE id = ? AND user_id = ?
    ");
    if ($del_t_stmt) {
        $del_t_stmt->bind_param("ii", $delete_target_id, $user_id);
        if ($del_t_stmt->execute()) {
            $del_t_stmt->close();
            header("Location: dashboard.php?msg=target_deleted");
            exit();
        }
        $del_t_stmt->close();
    }
}

$stmt = $conn->prepare("SELECT name, total_donated FROM userdata WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_name_db = isset($user['name']) ? $user['name'] : 'User';
$total_donated = isset($user['total_donated']) ? (float)$user['total_donated'] : 0.00;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : $user_name_db;

$stmt_targets = $conn->prepare("SELECT * FROM targets WHERE user_id = ? ORDER BY created_at DESC");
if (!$stmt_targets) {
    die("Prepare failed: " . $conn->error);
}
$stmt_targets->bind_param("i", $user_id);
$stmt_targets->execute();
$result_targets = $stmt_targets->get_result();

$upcoming_targets = [];
$completed_targets = [];

while ($row = $result_targets->fetch_assoc()) {
    if (isset($row['status']) && $row['status'] !== 'pending') {
        $completed_targets[] = $row;
    } else {
        $upcoming_targets[] = $row;
    }
}

$active_targets_count = count($upcoming_targets);
$completed_targets_count = count($completed_targets);

$stmt_targets->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | StudyStake</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
        body { min-height: 100vh; display: flex; overflow: hidden; }
        header { position: fixed; top: 0; left: 0; width: 100%; background: #fff; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; border-radius: 0 0 20px 20px; }
        header h1 { font-size: 28px; color: #FFB100; font-weight: 700; }
        header a { background-color: #333; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 14px; transition: 0.3s; }
        header a:hover { background-color: #555; }
        
        .left-visual { 
            width: 60%; 
            height: 100vh; 
            background: linear-gradient(120deg, #FFD93D, #FFB100); 
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: 0; 
            padding: 40px 20px 20px; 
            overflow-y: auto; 
            padding-top: 100px;
        }
        
        .dashboard { 
            background: #fff; 
            border-radius: 10px; 
            padding: 25px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
            width: 85%; 
            margin-left: auto; 
            margin-right: auto; 
            display: flex; 
            flex-direction: column;
        }
        
        .dashboard h2 { font-size: 24px; color: #333; margin-bottom: 20px; }
        .user-info { display: flex; align-items: center; margin-bottom: 20px; }
        .user-info img { width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; }
        .user-info .username { font-size: 18px; color: #333; font-weight: 600; }
        .dashboard .info { margin-bottom: 15px; }
        .dashboard .info label { font-weight: 600; display: block; margin-bottom: 5px; }
        .dashboard .info .value { font-size: 16px; color: #666; }
        .dashboard ul { list-style: none; margin-top: 0; padding-left: 0; width: 100%; text-align: left; }
        .dashboard ul li { background: #fff; padding: 12px; margin-bottom: 10px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .upcoming-targets { overflow-y: auto; padding-right: 10px; flex-grow: 1; }
        .upcoming-targets li { margin-bottom: 15px; }
        .right-content { margin-left: 60%; width: 40%; height: 100vh; overflow-y: auto; padding: 120px 40px 40px; background: #fdfdfd; }
        .container { background: #fff; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 30px 30px; margin-bottom: 30px; }
        h2 { text-align: center; margin-bottom: 25px; color: #333; }
        form { display: flex; flex-direction: column; gap: 20px; }
        label { font-weight: 500; margin-bottom: 6px; display: block; }
        input, select { width: 100%; padding: 10px; border-radius: 8px; border: 2px solid #FFD93D; outline: none; transition: 0.3s; font-size: 15px; }
        input:focus, select:focus { border-color: #ffcc00; background-color: #fffce0; }
        button { background-color: #FFD93D; color: #333; border: none; padding: 12px; border-radius: 8px; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        button:hover { background-color: #ffcc00; }
        .back-link { text-align: center; margin-top: 15px; }
        .back-link a { color: #333; text-decoration: underline; font-size: 14px; }
        .back-link a:hover { color: #ffb703; }
        .charity-details { background-color: #F9F9F9; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); height: fit-content; }
        .charity-details h3 { text-align: center; color: #FFB100; font-size: 24px; margin-bottom: 20px; }
        .charity-details .charity-item { background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 15px; margin-bottom: 20px; }
        .charity-details .charity-item h4 { color: #FFB100; font-size: 18px; margin-bottom: 8px; }
        .charity-details .charity-item p { font-size: 14px; color: #333; }
        
        .target-section { 
            background-color: #f9f9f9; 
            border-radius: 8px; 
            padding: 20px; 
            margin-top: 15px; 
            text-align: center; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); 
            min-height: 100px; 
            display: grid;
            place-items: center; 
            padding-top: 0; 
            max-height: 250px; 
            overflow-y: auto;
        }
        
        .target-section.empty { color: #999; font-style: italic; }
        .target-section + h3 { margin-top: 30px; }
        @media (max-width: 960px) {
            .left-visual { display: none; }
            .right-content { margin-left: 0; width: 100%; padding: 120px 20px 40px; }
        }
    </style>
</head>
<body>

    <header>
        <h1>StudyStake</h1>
        <a href="logout.php">Log Out</a>
    </header>

    <div class="left-visual">
        <div class="dashboard">
            <h2>User Dashboard</h2>

            <?php
            if (!isset($_SESSION['user_id'])) {
                echo "Session user_id not set.";
                exit();
            }
            ?>

            <div class="user-info">
                <img src="https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png" alt="Profile Picture">
                <div class="username">Welcome, <?php echo htmlspecialchars($user_name); ?>!</div>
            </div>

            <div class="info">
                <label for="current-targets">Current Targets</label>
                <div class="value" id="current-targets">
                    <?php echo $active_targets_count; ?> Active Targets
                </div>
            </div>

            <div class="info">
                <label for="completed-targets">Completed Targets</label>
                <div class="value" id="completed-targets">
                    <?php echo $completed_targets_count; ?> Completed Targets
                </div>
            </div>

            <div class="info">
                <label for="total-donated">Total Money Donated</label>
                <div class="value" id="total-donated">
                    <?php echo "₱" . number_format($total_donated, 2); ?>
                </div>
            </div>

            <div class="info">
                <label for="last-achievement">Last Achievement</label>
                <div class="value" id="last-achievement">Achieved target in Math 101: 90%</div> 
            </div>

            <h3>Upcoming Targets</h3>
            <div class="target-section <?php echo empty($upcoming_targets) ? 'empty' : ''; ?>">
                <?php if (empty($upcoming_targets)): ?>
                    There are currently no active targets.
                <?php else: ?>
                    <ul>
                        <?php foreach ($upcoming_targets as $target): ?>
                            <li>
                                <?php echo htmlspecialchars($target['subject_code']); ?>:
                                Target Grade <?php echo (int)$target['target_grade']; ?>%,
                                Bet: ₱<?php echo htmlspecialchars(number_format($target['bet_amount'], 2)); ?>,
                                Charity: <?php echo htmlspecialchars($target['charity']); ?>
                                <a href="dashboard.php?delete_target=<?php echo intval($target['id']); ?>" onclick="return confirm('Delete this target?');" style="margin-left:10px;color:#c33;text-decoration:none;">Delete</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <h3>Completed Targets</h3>
            <div class="target-section <?php echo empty($completed_targets) ? 'empty' : ''; ?>">
                <?php if (empty($completed_targets)): ?>
                    There are currently no completed targets.
                <?php else: ?>
                    <ul>
                        <?php foreach ($completed_targets as $target): ?>
                            <li>
                                <?php echo htmlspecialchars($target['subject_code']); ?>:
                                Achieved **<?php echo (int)$target['achieved_grade']; ?>%** (Target: <?php echo (int)$target['target_grade']; ?>%)
                                <br>
                                Status: **<?php echo ucfirst(htmlspecialchars($target['status'])); ?>**
                                <?php if ($target['status'] === 'failed'): ?>
                                    (Donation to <?php echo htmlspecialchars($target['charity']); ?>: ₱<?php echo htmlspecialchars(number_format($target['bet_amount'], 2)); ?>)
                                <?php endif; ?>
                                Completed on <?php echo htmlspecialchars(date('M d, Y', strtotime($target['date_completed']))); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="right-content">
        <div class="container">
            <h2>Set a New Target</h2>
            <form action="save_target.php" method="POST">
                <div>
                    <label for="subject">Subject Code</label>
                    <input type="text" id="subject" name="subject" placeholder="e.g., Math 101" required>
                </div>

                <div>
                    <label for="target">Target Grade (%)</label>
                    <input type="number" id="target" name="target" placeholder="e.g., 90" min="50" max="100" required>
                </div>

                <div>
                    <label for="bet">Bet Amount (₱)</label>
                    <input type="number" id="bet" name="bet" placeholder="e.g., 500" min="100" required>
                </div>

                <div>
                    <label for="charity">Choose a Charity (if goal not met)</label>
                    <select id="charity" name="charity" required>
                        <option value="">-- Select Charity --</option>
                        <option value="unicef">UNICEF Philippines</option>
                        <option value="redcross">Philippine Red Cross</option>
                        <option value="gawad-kalinga">Gawad Kalinga</option>
                        <option value="tahanan">Tahanan ng Pagmamahal</option>
                    </select>
                </div>

                <div>
                    <label for="method">Payment Method</label>
                    <select id="method" name="method" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                        <option value="paypal">PayPal</option>
                        <option value="card">Credit / Debit Card</option>
                    </select>
                </div>

                <div>
                    <label>Upload Proof of Payment</label>
                    <input type="file" name="receipt" accept="image/*" required>
                </div>

                <button type="submit">Save Target</button>
            </form>
        </div>

        <div class="container">
            <h2>Mark a Target as Complete</h2>
            <form id="complete-target-form" method="POST">
                <div>
                    <label for="active-target">Select Active Target</label>
                    <select id="active-target" name="target_id" required>
                        <option value="">-- Select Target --</option>
                        <?php foreach ($upcoming_targets as $target): ?>
                            <option value="<?php echo htmlspecialchars($target['id']); ?>">
                                <?php echo htmlspecialchars($target['subject_code']); ?> (Target: <?php echo (int)$target['target_grade']; ?>%, Bet: ₱<?php echo htmlspecialchars(number_format($target['bet_amount'], 2)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="achieved-grade">Enter Achieved Grade</label>
                    <input type="number" id="achieved-grade" name="achieved_grade" placeholder="e.g., 85" min="0" max="100" required>
                </div>
                
                <div>
                    <label>Upload image of grades</label>
                    <input type="file" name="grade_proof" accept="image/*" required>
                </div>

                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="charity-details">
            <h3>Learn About the Charities</h3>
            <div class="charity-item">
                <h4>UNICEF Philippines</h4>
                <p>UNICEF works to protect the rights of children by improving education, healthcare, and protection services in the Philippines.</p>
            </div>
            <div class="charity-item">
                <h4>Philippine Red Cross</h4>
                <p>Provides disaster relief, health services, and humanitarian assistance during crises.</p>
            </div>
            <div class="charity-item">
                <h4>Gawad Kalinga</h4>
                <p>Focuses on community development and eradicating poverty through housing and livelihood programs.</p>
            </div>
            <div class="charity-item">
                <h4>Tahanan ng Pagmamahal</h4>
                <p>A home for abandoned and neglected children, aiming to rehabilitate and protect them.</p>
            </div>
        </div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['target_id'], $_POST['achieved_grade'])) {
        $target_id = (int) $_POST['target_id'];
        $achieved_grade = (int) $_POST['achieved_grade'];

        $stmt = $conn->prepare("SELECT target_grade, bet_amount, charity FROM targets WHERE id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $target_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $target = $result->fetch_assoc();
            $target_grade = (int) $target['target_grade'];
            $bet_amount = (float) $target['bet_amount'];
            $charity = $target['charity'];

            $status = ($achieved_grade >= $target_grade) ? 'completed' : 'failed';
            $result_text = ($achieved_grade >= $target_grade) ? 'met' : 'not met';

            $update_target_stmt = $conn->prepare("UPDATE targets SET status = ?, achieved_grade = ?, result = ?, date_completed = NOW() WHERE id = ? AND user_id = ?");
            $update_target_stmt->bind_param("ssiii", $status, $achieved_grade, $result_text, $target_id, $user_id);
            
            if (!$update_target_stmt->execute()) {
                error_log("Target Update Failed: " . $update_target_stmt->error);
                echo "<script>alert('Error: Could not update target record. " . addslashes($update_target_stmt->error) . "');</script>";
            } else {
                if ($status === 'failed') {
                    $update_donation_stmt = $conn->prepare("UPDATE userdata SET total_donated = total_donated + ? WHERE id = ?");
                    $update_donation_stmt->bind_param("di", $bet_amount, $user_id);

                    if (!$update_donation_stmt->execute()) {
                        error_log("Donation Update Failed: " . $update_donation_stmt->error);
                        echo "<script>alert('Warning: Target failed, but donation update failed. " . addslashes($update_donation_stmt->error) . "');</script>";
                    }
                    $update_donation_stmt->close();
                }
                
                header("Location: dashboard.php?msg=target_processed&status=" . $status);
                exit();
            }

            $update_target_stmt->close();
        } else {
             echo "<script>alert('Error: Target not found or already processed.');</script>";
        }

        $stmt->close();
    }
    ?>

</body>
</html>