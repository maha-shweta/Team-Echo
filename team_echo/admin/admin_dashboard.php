<?php
session_start();

// Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Statistics
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
$totalUsers = $tableCheck->num_rows > 0 ? $conn->query("SELECT COUNT(*) as total FROM management_user")->fetch_assoc()['total'] : 0;
$totalFeedback = $conn->query("SELECT COUNT(*) as total FROM feedback")->fetch_assoc()['total'];
$resolvedFeedback = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE is_resolved=1")->fetch_assoc()['total'];
$pendingFeedback = $totalFeedback - $resolvedFeedback;

// Feedback for table
$sql = "SELECT f.feedback_id, f.feedback_text, f.submitted_at, c.category_name, f.is_resolved 
        FROM feedback f
        JOIN category c ON f.category_id = c.category_id";
$result = $conn->query($sql);

// Category-wise feedback for charts
$catResult = $conn->query("SELECT c.category_name, COUNT(f.feedback_id) as count 
                           FROM category c 
                           LEFT JOIN feedback f ON c.category_id=f.category_id 
                           GROUP BY c.category_id");
$categories = [];
$catCounts = [];
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['category_name'];
    $catCounts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: 'Inter', sans-serif; margin:0; background:#f6f8f9; }
.header { background: linear-gradient(135deg,#0a5a52,#083e38); color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:24px; margin:0; font-weight:700; }
.header button { background:#ffffff22; border:1px solid #ffffff44; padding:8px 16px; border-radius:6px; font-size:14px; cursor:pointer; color:white; transition:0.3s; }
.header button:hover { background:#ffffff33; }

/* Page Wrapper */
.container { padding:25px; }

/* Main Button */
.main-btn { background:#064c44; color:white; padding:10px 18px; display:inline-block; border-radius:6px; font-size:14px; text-decoration:none; font-weight:600; margin-bottom:20px; transition:0.3s; }
.main-btn:hover { background:#043d36; }

/* Statistics Cards */
.stats { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:25px; justify-content: space-between; }
.card { background:white; padding:20px; border-radius:10px; flex:1 1 180px; box-shadow:0 3px 8px rgba(0,0,0,0.1); text-align:center; }
.card h3 { font-size:16px; margin:0; color:#064c44; font-weight:600; }
.card p { font-size:20px; margin:8px 0 0; font-weight:700; }

/* Charts */
.chart-container {
    display: flex;
    gap: 30px;
    justify-content: space-between;
    flex-wrap: nowrap;   /* Prevent shrinking */
    margin-top: 30px;
    margin-left: 100px;
    margin-right: 100px;
}

.chart-container canvas {
    width: 380px !important;   /* EXACT width like the image */
    height: 380px !important;  /* EXACT height like the image */
}

/* Table */
table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; box-shadow:0px 2px 6px rgba(0,0,0,0.1); font-size:14px; }
th { background:#064c44; color:white; padding:12px; font-weight:600; text-align:left; }
td { padding:10px; border-bottom:1px solid #eee; color:#333; }
tr:hover td { background:#f3fdfa; }
a.action-link { color:#064c44; font-weight:600; text-decoration:none; font-size:13px; }
a.action-link:hover { text-decoration:underline; }

/* Filter */
.filter { margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap; }
.filter input, .filter select { padding:6px; border-radius:6px; border:1px solid #ccc; font-size:13px; }

/* Toast */
.toast { position: fixed; top:15px; right:15px; background:#064c44; color:white; padding:10px 14px; border-radius:6px; box-shadow:0 2px 6px rgba(0,0,0,0.2); display:none; z-index:1000; font-size:13px; }

/* Responsive */
@media (max-width:1024px) {
    .chart-container canvas { max-width:300px; height:220px;}
    .card { flex:1 1 140px; padding:16px; }
}
@media (max-width:768px) {
    .chart-container { flex-direction: column; gap:15px; }
    .stats { flex-direction: column; gap:12px; }
    table th, table td { padding:10px; font-size:13px; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1>Welcome, Admin <?php echo $_SESSION['name']; ?></h1>
    <form action="../logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</div>

<div class="container">

    <a href="manage_users.php" class="main-btn">Manage Users & HR</a>

    <!-- Statistics -->
    <div class="stats">
        <div class="card"><h3>Total Users</h3><p><?php echo $totalUsers; ?></p></div>
        <div class="card"><h3>Total Feedback</h3><p><?php echo $totalFeedback; ?></p></div>
        <div class="card"><h3>Pending</h3><p><?php echo $pendingFeedback; ?></p></div>
        <div class="card"><h3>Resolved</h3><p><?php echo $resolvedFeedback; ?></p></div>
    </div>

    <!-- Charts -->
    <div class="chart-container">
        <canvas id="barChart"></canvas>
        <canvas id="pieChart"></canvas>
    </div>

    <!-- Filter -->
    <div class="filter">
        <input type="text" id="filterCategory" placeholder="Search by category">
        <select id="filterResolved">
            <option value="">All</option>
            <option value="1">Resolved</option>
            <option value="0">Pending</option>
        </select>
        <button onclick="applyFilter()">Apply Filter</button>
    </div>

    <!-- Feedback Table -->
    <h2>Feedback List</h2>
    <table id="feedbackTable">
        <tr>
            <th>ID</th><th>Category</th><th>Feedback</th><th>Submitted At</th><th>Resolved</th><th>Actions</th>
        </tr>
        <?php if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['feedback_id']; ?></td>
                    <td><?php echo $row['category_name']; ?></td>
                    <td><?php echo $row['feedback_text']; ?></td>
                    <td><?php echo $row['submitted_at']; ?></td>
                    <td><?php echo $row['is_resolved'] ? '✔ Yes' : '✖ No'; ?></td>
                    <td>
                        <a class="action-link" href="../feedback/resolve_feedback.php?id=<?php echo $row['feedback_id']; ?>">Resolve</a> |
                        <a class="action-link" href="../feedback/delete_feedback.php?id=<?php echo $row['feedback_id']; ?>" onclick="return confirm('Are you sure you want to delete this feedback?');">Delete</a>
                    </td>
                </tr>
        <?php } } else { echo "<tr><td colspan='6'>No feedback available!</td></tr>"; } ?>
    </table>
</div>

<div class="toast" id="toast"></div>

<script>
// Charts
const categories = <?php echo json_encode($categories); ?>;
const counts = <?php echo json_encode($catCounts); ?>;

function generateGradients(ctx, count) {
    const gradients = [];
    const baseColors = [
        ['#FF6384','#FF99A4'],['#36A2EB','#69B8FF'],['#FFCE56','#FFE080'],
        ['#4BC0C0','#80DADA'],['#9966FF','#B499FF'],['#FF9F40','#FFB777'],
        ['#8BC34A','#A9D272'],['#E91E63','#F0638A'],['#00BCD4','#33CDD7'],['#FFC107','#FFD454']
    ];
    for(let i=0;i<count;i++){
        const grad = ctx.createLinearGradient(0,0,0,400);
        const c = baseColors[i % baseColors.length];
        grad.addColorStop(0,c[0]);
        grad.addColorStop(1,c[1]);
        gradients.push(grad);
    }
    return gradients;
}

// Bar Chart
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx,{
    type:'bar',
    data:{ labels:categories, datasets:[{ label:'Feedback Count', data:counts, backgroundColor:generateGradients(barCtx,categories.length) }] },
    options:{ responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true},x:{ticks:{font:{size:12}}}}, animation:{duration:1000} }
});

// Pie Chart
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx,{
    type:'pie',
    data:{ labels:categories, datasets:[{ data:counts, backgroundColor:[
        '#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8BC34A','#E91E63','#00BCD4','#FFC107'
    ].slice(0,categories.length) }]},
    options:{ responsive:true, animation:{animateRotate:true,animateScale:true,duration:1000} }
});

// Filter Table
function applyFilter(){
    const category = document.getElementById('filterCategory').value.toLowerCase();
    const resolved = document.getElementById('filterResolved').value;
    const rows = document.querySelectorAll('#feedbackTable tr');
    rows.forEach((row,index)=>{
        if(index===0) return;
        const catText = row.cells[1].innerText.toLowerCase();
        const resText = row.cells[4].innerText.includes('✔') ? '1':'0';
        row.style.display = ( (category === '' || catText.includes(category)) && (resolved === '' || resText === resolved) ) ? '' : 'none';
    });
}

// Toast Message Example
function showToast(msg){
    const toast = document.getElementById('toast');
    toast.innerText = msg;
    toast.style.display='block';
    setTimeout(()=>{toast.style.display='none';},3000);
}
</script>

</body>
</html>
<?php $conn->close(); ?>
