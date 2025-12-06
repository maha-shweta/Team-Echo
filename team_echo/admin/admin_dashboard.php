<?php
session_start();

// Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Statistics
$tableCheck = $conn->query("SHOW TABLES LIKE 'management_user'");
$totalUsers = $tableCheck->num_rows > 0 ? $conn->query("SELECT COUNT(*) as total FROM management_user")->fetch_assoc()['total'] : 0;
$totalFeedback = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$resolvedFeedback = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE is_resolved=1")->fetch_assoc()['total'];
$pendingFeedback = $totalFeedback - $resolvedFeedback;

// Feedback table
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_resolved 
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id
        ORDER BY f.submitted_at DESC";
$result = $conn->query($sql);

// Category-wise chart data
$catResult = $conn->query("SELECT c.category_name, COUNT(f.feedback_id) as count 
                           FROM category c 
                           LEFT JOIN feedback f ON c.category_id=f.category_id 
                           GROUP BY c.category_id");
$categories = [];
$catCounts = [];
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['category_name'];
    $catCounts[] = (int)$row['count'];
}

// Sentiment
$sentimentResult = $conn->query("SELECT feedback_text FROM feedback");
$sentiments = ['Positive'=>0, 'Neutral'=>0, 'Negative'=>0];

while($row = $sentimentResult->fetch_assoc()){
    $text = strtolower($row['feedback_text']);
    if(strpos($text,'good')!==false || strpos($text,'excellent')!==false || strpos($text,'happy')!==false || strpos($text,'great')!==false || strpos($text,'love')!==false){
        $sentiments['Positive']++;
    } elseif(strpos($text,'bad')!==false || strpos($text,'poor')!==false || strpos($text,'angry')!==false || strpos($text,'hate')!==false || strpos($text,'terrible')!==false){
        $sentiments['Negative']++;
    } else {
        $sentiments['Neutral']++;
    }
}

$sentimentLabels = array_keys($sentiments);
$sentimentCounts = array_values($sentiments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<style>
:root{
    --bg:#f6f8f9;
    --primary:#064c44;
    --card:#ffffff;
    --muted:#6b7280;
    --accent-shadow: rgba(2,6,23,0.08);
}
*{box-sizing:border-box;}
body { font-family: 'Inter', sans-serif; margin:0; background:var(--bg); color:#1f2937; }

.header { background: linear-gradient(135deg,#0a5a52,#083e38); color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:20px; margin:0; font-weight:700; letter-spacing:0.2px; }
.header form button { background:#ffffff22; border:1px solid #ffffff33; padding:8px 14px; border-radius:8px; font-size:14px; cursor:pointer; color:white; transition:0.25s; }
.header form button:hover { background:#ffffff33; transform:translateY(-1px); }

.container { display:flex; flex-wrap:wrap; margin:0; }

/* LEFT COLUMN: Stats cards */
.left-col {
    flex: 0 0 220px;
    padding: 20px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}
.stats { display:flex; flex-direction:column; gap:12px; }
.card { background:var(--card); padding:14px; border-radius:12px; box-shadow:0 8px 24px var(--accent-shadow); text-align:center; }
.card .icon-box { width:38px; height:38px; border-radius:10px; background:var(--primary); color:white; display:inline-flex; align-items:center; justify-content:center; font-size:16px; margin-bottom:6px; }
.card h3 { font-size:13px; margin:0 0 4px; color:var(--primary); font-weight:700; }
.card p { font-size:18px; margin:0; font-weight:700; color:#111827; }

/* RIGHT COLUMN */
.right-col { flex:1; padding:20px; min-width:300px; }
.filter-row { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:12px; align-items:center; }
.filter-row input, .filter-row select { padding:6px 10px; border-radius:6px; border:1px solid #e5e7eb; font-size:13px; }
.filter-row button, .filter-row .main-btn { padding:6px 12px; font-size:13px; }

.charts-row-right { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
.chart-card { flex:1 1 48%; min-width:200px; text-align:center; padding:14px; border-radius:12px; background:var(--card); box-shadow: 0 8px 24px var(--accent-shadow); }
.chart-wrap { height:220px; display:flex; align-items:center; justify-content:center; }

table { width:100%; border-collapse:collapse; background:var(--card); border-radius:8px; overflow:hidden; box-shadow:0 6px 18px rgba(2,6,23,0.04); }
th { background:var(--primary); color:white; padding:10px; font-weight:600; text-align:left; font-size:13px; }
td { padding:10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; color:#374151; font-size:13px; }
tr:hover td { background:#fbfefb; }
.action-link { color:var(--primary); text-decoration:none; font-weight:600; }
.action-link:hover { text-decoration:underline; }

@media (max-width:1000px){.left-col{position:relative; height:auto; flex:1 1 100%;}.right-col{flex:1 1 100%;}}
</style>
</head>
<body>

<div class="header">
    <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
    <form action="../logout.php" method="post">
        <button type="submit"><i class="fa fa-sign-out-alt" style="margin-right:6px;"></i>Logout</button>
    </form>
</div>

<div class="container">

    <!-- LEFT COLUMN: STATS CARDS -->
    <div class="left-col">
        <div class="stats">
            <div class="card"><div class="icon-box"><i class="fas fa-users"></i></div><h3>Total Users</h3><p><?php echo (int)$totalUsers; ?></p></div>
            <div class="card"><div class="icon-box"><i class="fas fa-comment-dots"></i></div><h3>Total Feedback</h3><p><?php echo (int)$totalFeedback; ?></p></div>
            <div class="card"><div class="icon-box"><i class="fas fa-clock"></i></div><h3>Pending</h3><p><?php echo (int)$pendingFeedback; ?></p></div>
            <div class="card"><div class="icon-box"><i class="fas fa-check-circle"></i></div><h3>Resolved</h3><p><?php echo (int)$resolvedFeedback; ?></p></div>
            <div class="card"><div class="icon-box" style="background:#009688;"><i class="fas fa-smile"></i></div><h3>Positive</h3><p><?php echo (int)$sentiments['Positive']; ?></p></div>
            <div class="card"><div class="icon-box" style="background:#9E9E9E;"><i class="fas fa-meh"></i></div><h3>Neutral</h3><p><?php echo (int)$sentiments['Neutral']; ?></p></div>
            <div class="card"><div class="icon-box" style="background:#F44336;"><i class="fas fa-angry"></i></div><h3>Negative</h3><p><?php echo (int)$sentiments['Negative']; ?></p></div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-col">
        <!-- FILTERS -->
        <div class="filter-row">
            <input type="text" id="filterCategory" placeholder="Search by category">
            <select id="filterResolved">
                <option value="">All</option>
                <option value="1">Resolved</option>
                <option value="0">Pending</option>
            </select>
            <select id="filterSentiment">
                <option value="">All Sentiments</option>
                <option value="Positive">Positive</option>
                <option value="Neutral">Neutral</option>
                <option value="Negative">Negative</option>
            </select>
            <button class="main-btn" onclick="applyFilter(); return false;">Apply Filter</button>
            <a href="manage_users.php" class="main-btn"><i class="fa fa-users" style="margin-right:4px;"></i>Manage Users & HR</a>
        </div>

        <!-- PIE CHARTS -->
        <div class="charts-row-right">
            <div class="chart-card">
                <h4>Feedback by Category</h4>
                <div class="chart-wrap">
                    <canvas id="categoryDonut"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Sentiment Analysis</h4>
                <div class="chart-wrap">
                    <canvas id="sentimentDonut"></canvas>
                </div>
            </div>
        </div>

        <!-- FEEDBACK TABLE -->
        <table id="feedbackTable">
            <tr>
                <th>ID</th><th>Category</th><th>Feedback</th><th>Submitted</th><th>Resolved</th><th>Sentiment</th><th>Actions</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $text = strtolower($row['feedback_text']);
                    if(strpos($text,'good')!==false || strpos($text,'excellent')!==false || strpos($text,'happy')!==false || strpos($text,'great')!==false || strpos($text,'love')!==false){
                        $sentiment='Positive';
                    } elseif(strpos($text,'bad')!==false || strpos($text,'poor')!==false || strpos($text,'angry')!==false || strpos($text,'hate')!==false || strpos($text,'terrible')!==false){
                        $sentiment='Negative';
                    } else {
                        $sentiment='Neutral';
                    }
            ?>
            <tr>
                <td><?= (int)$row['feedback_id'] ?></td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td><?= htmlspecialchars($row['feedback_text']) ?></td>
                <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                <td><?= $row['is_resolved'] ? '✔ Yes' : '✖ No' ?></td>
                <td><?= $sentiment ?></td>
                <td>
                    <a class="action-link" href="../feedback/resolve_feedback.php?id=<?= (int)$row['feedback_id'] ?>">Resolve</a> |
                    <a class="action-link" href="../feedback/delete_feedback.php?id=<?= (int)$row['feedback_id'] ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php } } else { echo "<tr><td colspan='7'>No feedback found</td></tr>"; } ?>
        </table>
    </div>

</div>

<script>
const categoryLabels = <?php echo json_encode($categories, JSON_HEX_TAG); ?>;
const categoryData = <?php echo json_encode($catCounts, JSON_HEX_TAG); ?>;
const sentimentLabels = <?php echo json_encode($sentimentLabels, JSON_HEX_TAG); ?>;
const sentimentData = <?php echo json_encode($sentimentCounts, JSON_HEX_TAG); ?>;

const categoryColors = ["#009688","#673AB7","#FFC107","#3F51B5","#FF5722","#8BC34A","#00BCD4"];
const sentimentColors = ["#009688","#9E9E9E","#F44336"];

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.plugins.tooltip.boxPadding = 8;
Chart.defaults.plugins.legend.labels.usePointStyle = true;

const centerTextPlugin = {
    id: 'centerTextPlugin',
    afterDraw(chart, args, options) {
        const ctx = chart.ctx;
        const width = chart.width;
        const height = chart.height;
        ctx.save();
        const dataset = chart.data.datasets[0];
        const total = dataset.data.reduce((a,b)=>a+b,0);
        ctx.font = "700 22px Inter";
        ctx.fillStyle = "#111827";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(total, width/2, height/2 - 6);
        ctx.font = "500 13px Inter";
        ctx.fillStyle = "#6b7280";
        ctx.fillText(options.label || "Total", width/2, height/2 + 18);
        ctx.restore();
    }
};

const catCtx = document.getElementById('categoryDonut').getContext('2d');
new Chart(catCtx, {
    type:'doughnut',
    data:{labels:categoryLabels,datasets:[{data:categoryData,backgroundColor:categoryColors,borderColor:'#fff',borderWidth:2,hoverOffset:12}]},
    options:{cutout:'68%',responsive:true,maintainAspectRatio:false,plugins:{centerTextPlugin:{label:'Categories'},legend:{display:true,position:'bottom',labels:{padding:12,boxWidth:12,font:{size:13,weight:'600'}}},datalabels:{color:'#fff',formatter:(value)=>{const total = categoryData.reduce((a,b)=>a+b,1); return Math.round((value/total)*100)+'%';},font:{weight:'700',size:13}},tooltip:{callbacks:{label:(context)=>{const label=context.label||'';const value=context.parsed||0;const total=categoryData.reduce((a,b)=>a+b,1);const percent=((value/total)*100).toFixed(1);return `${label}: ${value} (${percent}%)`;}}}},layout:{padding:8}},
    plugins:[ChartDataLabels,centerTextPlugin]
});

const senCtx = document.getElementById('sentimentDonut').getContext('2d');
new Chart(senCtx,{
    type:'doughnut',
    data:{labels:sentimentLabels,datasets:[{data:sentimentData,backgroundColor:sentimentColors,borderColor:'#fff',borderWidth:2,hoverOffset:12}]},
    options:{cutout:'70%',responsive:true,maintainAspectRatio:false,plugins:{centerTextPlugin:{label:'Sentiment'},legend:{display:true,position:'bottom',labels:{padding:12,boxWidth:12,font:{size:13,weight:'600'}}},datalabels:{color:'#fff',formatter:(value)=>{const total = sentimentData.reduce((a,b)=>a+b,1);return Math.round((value/total)*100)+'%';},font:{weight:'700',size:13}},tooltip:{callbacks:{label:(context)=>{const label=context.label||'';const value=context.parsed||0;const total=sentimentData.reduce((a,b)=>a+b,1);const percent=((value/total)*100).toFixed(1);return `${label}: ${value} (${percent}%)`;}}}},layout:{padding:8}},
    plugins:[ChartDataLabels,centerTextPlugin]
});

// Filter
function applyFilter(){
    const cat = document.getElementById("filterCategory").value.toLowerCase();
    const res = document.getElementById("filterResolved").value;
    const sen = document.getElementById("filterSentiment").value;

    document.querySelectorAll("#feedbackTable tr").forEach((r,i)=>{
        if(i===0) return;
        const c = r.cells[1].innerText.toLowerCase();
        const rs = r.cells[4].innerText.includes("✔") ? "1" : "0";
        const st = r.cells[5].innerText;
        r.style.display = (cat=="" || c.includes(cat)) && (res=="" || rs==res) && (sen=="" || st==sen) ? "" : "none";
    });
}
</script>

</body>
</html>

<?php $conn->close(); ?>
