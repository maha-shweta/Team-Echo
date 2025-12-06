<?php
session_start();

// Ensure only Admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}

include('../db/db.php');

// Search, Filter, Sort
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'user_id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Whitelist columns for sorting to prevent SQL injection
$allowed_columns = ['user_id','name','email','role','last_login'];
if(!in_array($sort_column,$allowed_columns)) $sort_column = 'user_id';
$sort_order = strtoupper($sort_order) == 'DESC' ? 'DESC' : 'ASC';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page-1) * $limit;

// Count total users for pagination
$count_sql = "SELECT COUNT(*) as total FROM management_user WHERE 1";
if($search) $count_sql .= " AND (name LIKE ? OR email LIKE ?)";
if($filter_role) $count_sql .= " AND role=?";
$stmt_count = $conn->prepare($count_sql);

if($search && $filter_role){
    $like_search = "%$search%";
    $stmt_count->bind_param("sss",$like_search,$like_search,$filter_role);
} elseif($search){
    $like_search = "%$search%";
    $stmt_count->bind_param("ss",$like_search,$like_search);
} elseif($filter_role){
    $stmt_count->bind_param("s",$filter_role);
}

$stmt_count->execute();
$total_result = $stmt_count->get_result();
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users/$limit);
$stmt_count->close();

// Fetch users with search, filter, sort, and pagination
$sql = "SELECT * FROM management_user WHERE 1";
$params = [];
$types = '';

if($search){
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if($filter_role){
    $sql .= " AND role=?";
    $params[] = $filter_role;
    $types .= 's';
}

$sql .= " ORDER BY $sort_column $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users & HR</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; margin:0; background:#f6f8f9; }
.header { background: linear-gradient(135deg,#0a5a52,#083e38); color:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; }
.header h1 { font-size:24px; margin:0; font-weight:700; }
.header button { background:#ffffff22; border:1px solid #ffffff44; padding:8px 16px; border-radius:6px; font-size:14px; cursor:pointer; color:white; transition:0.3s; }
.header button:hover { background:#ffffff33; }
.container { padding:25px; }
.main-btn { background:#064c44; color:white; padding:10px 18px; display:inline-block; border-radius:6px; font-size:14px; text-decoration:none; font-weight:600; margin-bottom:20px; transition:0.3s; }
.main-btn:hover { background:#043d36; }
table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; box-shadow:0 3px 8px rgba(0,0,0,0.1); font-size:14px; }
th { background:#064c44; color:white; padding:12px; font-weight:600; text-align:left; cursor:pointer; }
td { padding:10px; border-bottom:1px solid #eee; color:#333; }
tr:hover td { background:#f3fdfa; }
a.action-link { color:#064c44; font-weight:600; text-decoration:none; font-size:13px; }
a.action-link:hover { text-decoration:underline; }
input[type=text], select { padding:6px 10px; margin-right:8px; border:1px solid #ccc; border-radius:4px; }
button.search-btn { padding:6px 12px; border:none; border-radius:4px; background:#064c44; color:white; cursor:pointer; transition:0.3s; }
button.search-btn:hover { background:#043d36; }
.pagination { margin-top:15px; display:flex; gap:6px; flex-wrap:wrap; }
.pagination a { padding:6px 10px; background:#064c44; color:white; text-decoration:none; border-radius:4px; transition:0.3s; }
.pagination a:hover, .pagination a.active { background:#043d36; }
@media (max-width:768px) { table th, table td { padding:8px; font-size:13px; } }
</style>
<script>
function confirmDelete(name){
    return confirm("Are you sure you want to delete user: " + name + "?");
}

// Sort function
function sortColumn(column){
    const urlParams = new URLSearchParams(window.location.search);
    let currentSort = urlParams.get('sort');
    let currentOrder = urlParams.get('order') || 'ASC';
    if(currentSort === column){
        currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        currentOrder = 'ASC';
    }
    urlParams.set('sort', column);
    urlParams.set('order', currentOrder);
    window.location.search = urlParams.toString();
}
</script>
</head>
<body>

<div class="header">
    <h1>Manage Users & HR</h1>
    <form action="../logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</div>

<div class="container">
    <a href="admin_dashboard.php" class="main-btn">Back to Dashboard</a>
    <a href="add_user.php" class="main-btn">Add New User</a>

    <!-- Search & Filter -->
    <form method="GET" style="margin-bottom:15px;">
        <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
        <select name="role">
            <option value="">All Roles</option>
            <option value="admin" <?php if($filter_role=='admin') echo 'selected'; ?>>Admin</option>
            <option value="hr" <?php if($filter_role=='hr') echo 'selected'; ?>>HR</option>
            <option value="user" <?php if($filter_role=='user') echo 'selected'; ?>>User</option>
        </select>
        <button type="submit" class="search-btn">Search</button>
    </form>

    <!-- Users Table -->
    <table>
        <tr>
            <th onclick="sortColumn('user_id')">ID</th>
            <th onclick="sortColumn('name')">Name</th>
            <th onclick="sortColumn('email')">Email</th>
            <th onclick="sortColumn('role')">Role</th>
            <th onclick="sortColumn('last_login')">Last Login</th>
            <th>Actions</th>
        </tr>
        <?php
        if($result->num_rows>0){
            while($row=$result->fetch_assoc()){
                echo "<tr>
                        <td>{$row['user_id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['role']}</td>
                        <td>".($row['last_login']??'-')."</td>
                        <td>
                            <a class='action-link' href='edit_user.php?id={$row['user_id']}'>Edit</a> | 
                            <a class='action-link' href='delete_user.php?id={$row['user_id']}' onclick=\"return confirmDelete('{$row['name']}');\">Delete</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No users found.</td></tr>";
        }
        ?>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php
        for($i=1;$i<=$total_pages;$i++){
            $active = $i==$page ? 'active' : '';
            $params = $_GET;
            $params['page'] = $i;
            echo "<a class='$active' href='?".http_build_query($params)."'>$i</a>";
        }
        ?>
    </div>

</div>

<?php $stmt->close(); $conn->close(); ?>
</body>
</html>
