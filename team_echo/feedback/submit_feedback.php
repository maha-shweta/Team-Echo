<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit;
}

include('../db/db.php');

$sql = "SELECT * FROM category";
$result = $conn->query($sql);

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = $_POST['category_id'];
    $feedback_text = $_POST['feedback_text'];

    if (empty($category_id) || empty($feedback_text)) {
        $message = "Please fill out all fields.";
        $messageType = 'error';
    } else {
        // ---- Send feedback to Flask AI API ----
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:5000/analyze"); // Flask AI API endpoint
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['feedback' => $feedback_text]));
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            $message = "Error contacting AI API: " . curl_error($ch);
            $messageType = 'error';
        } else {
            $ai_result = json_decode($response, true);
            $sentiment_score = $ai_result['sentiment_score'] ?? null;
            $sentiment_label = $ai_result['sentiment_label'] ?? null;

            // ---- Insert feedback with AI results into DB ----
            $sql = "INSERT INTO feedback (category_id, feedback_text, is_anonymous, sentiment_score, sentiment_label) VALUES (?, ?, 1, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isds", $category_id, $feedback_text, $sentiment_score, $sentiment_label);
                if ($stmt->execute()) {
                    $message = "Feedback submitted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Database Error: " . $stmt->error;
                    $messageType = 'error';
                }
                $stmt->close();
            } else {
                $message = "Database Error: " . $conn->error;
                $messageType = 'error';
            }
        }
        curl_close($ch);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Feedback</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
/* --- Keep your existing CSS --- */
body { font-family: 'Inter', sans-serif; margin:0; background:#f6f8f9; }
.wrapper { display:flex; max-width:1000px; margin:50px auto; background:white; border-radius:16px; box-shadow:0 6px 20px rgba(0,0,0,0.15); overflow:hidden; }
.left { flex:1; background:linear-gradient(135deg,#064c44,#0ca986); display:flex; align-items:center; justify-content:center; padding:40px; color:white; }
.animated-text { font-size:28px; font-weight:700; overflow:hidden; white-space:nowrap; border-right:4px solid white; width:0; animation:typing 3s steps(30,end) forwards, blink 0.7s step-end infinite; }
@keyframes typing { from{width:0;} to{width:100%;} }
@keyframes blink { 50%{border-color:transparent;} }
.right { flex:1; padding:40px 30px; }
h2 { color:#064c44; font-weight:700; margin-bottom:30px; text-align:center; }
.form-group { position:relative; margin-bottom:25px; }
.form-group select, .form-group textarea { width:100%; padding:16px 12px; border-radius:8px; border:1px solid #ccc; font-size:14px; outline:none; background:none; }
.form-group label { position:absolute; top:16px; left:12px; color:#888; font-size:14px; pointer-events:none; transition:0.3s; }
.form-group select:focus + label, .form-group select:not(:placeholder-shown) + label, .form-group textarea:focus + label, .form-group textarea:not(:placeholder-shown) + label { top:-8px; left:10px; font-size:12px; color:#064c44; background:white; padding:0 4px; }
input[type="submit"] { background:linear-gradient(135deg,#064c44,#0ca986); color:white; padding:14px 20px; border:none; border-radius:10px; font-size:16px; font-weight:600; cursor:pointer; width:100%; transition:0.3s; }
input[type="submit"]:hover { background:linear-gradient(135deg,#043d36,#08a68f); }
#charCount { text-align:right; font-size:12px; color:#555; margin-top:-18px; margin-bottom:12px; }
.toast { position:fixed; top:20px; right:20px; padding:14px 20px; border-radius:10px; color:white; font-weight:600; opacity:0; transform:translateY(-20px); transition:opacity 0.4s, transform 0.4s; z-index:1000; }
.toast.show { opacity:1; transform:translateY(0); }
.toast.success { background:#28a745; }
.toast.error { background:#dc3545; }
@media(max-width:900px){ .wrapper{ flex-direction:column;} .left, .right{ flex:unset; padding:30px;} .animated-text{text-align:center; border-right:4px solid white; } }
</style>
</head>
<body>

<div class="wrapper">
    <div class="left">
        <div class="animated-text">📝 Submit Feedback Anonymously</div>
    </div>

    <div class="right">
        <form method="POST" action="">
            <div class="form-group">
                <select name="category_id" required>
                    <option value="" selected hidden></option>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row["category_id"] . "'>" . htmlspecialchars($row["category_name"]) . "</option>";
                        }
                    }
                    ?>
                </select>
                <label>Category</label>
            </div>

            <div class="form-group">
                <textarea name="feedback_text" id="feedback_text" rows="5" maxlength="500" required placeholder=" "></textarea>
                <label>Your Feedback</label>
                <div id="charCount">0 / 500</div>
            </div>

            <input type="submit" value="Submit Feedback">
        </form>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
const textarea = document.getElementById('feedback_text');
const charCount = document.getElementById('charCount');

textarea.addEventListener('input', () => {
    charCount.textContent = `${textarea.value.length} / 500`;
});

function showToast(message, type='success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => toast.className = 'toast', 4000);
}

<?php if($message): ?>
showToast("<?php echo addslashes($message); ?>", "<?php echo $messageType; ?>");
<?php endif; ?>
</script>

</body>
</html>
