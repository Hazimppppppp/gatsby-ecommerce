<?php
// File to store user data
$dataFile = "data.txt";

// Fetch users from the file
function getUsers()
{
    global $dataFile;
    if (!file_exists($dataFile)) {
        file_put_contents($dataFile, '');
    }
    $data = file_get_contents($dataFile);
    return $data ? json_decode($data, true) : array();
}

// Save users to the file
function saveUsers($users)
{
    global $dataFile;
    file_put_contents($dataFile, json_encode($users));
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $users = getUsers();

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $expirationDate = date('d/m/Y', strtotime($_POST['subscription']));
            $users[] = array(
                'name' => $_POST['name'],
                'subscription' => $_POST['subscription'],
                'expiration' => $expirationDate,
                'status' => 'نشط',
            );
        } elseif ($action === 'edit') {
            $index = $_POST['index'];
            $expirationDate = $_POST['expiration'];
            $expirationTimestamp = DateTime::createFromFormat('d/m/Y', $expirationDate)->getTimestamp();
            $currentTimestamp = time();
            $status = $expirationTimestamp < $currentTimestamp ? 'منتهي' : 'نشط';

            $users[$index]['name'] = $_POST['name'];
            $users[$index]['expiration'] = $expirationDate;
            $users[$index]['status'] = $status;
        } elseif ($action === 'delete') {
            $index = $_POST['index'];
            array_splice($users, $index, 1);
        } elseif ($action === 'toggle') {
            $index = $_POST['index'];
            $users[$index]['status'] = ($users[$index]['status'] === 'نشط') ? 'معلق' : 'نشط';
        }

        saveUsers($users);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$users = getUsers();
?>
<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الصالة الرياضية</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f4f4f9;
            font-family: 'IBM Plex Sans Arabic', sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-custom {
            background-color: #ff4757;
            color: #fff;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background-color: #e84118;
        }

        .btn-outline-custom {
            border-color: #ff4757;
            color: #ff4757;
        }

        .btn-outline-custom:hover {
            background-color: #ff4757;
            color: #fff;
        }

        .table thead {
            background-color: #ff4757;
            color: #fff;
        }

        .stats-card {
            background: linear-gradient(45deg, #ff6b81, #ff4757);
            color: #fff;
        }

        h1 {
            font-weight: bold;
            font-size: 2.5rem;
            color: #ff4757;
            text-align: center;
        }

        .btn-no-frame {
            border: none;
            background: none;
            padding: 0;
        }

        .material-icons {
            font-size: 1.5rem;
            color: #ff4757; /* Color same as Add button */
        }

        /* Styling for the search box */
        .search-box {
            margin-bottom: 15px;
        }
    </style>
</head>

<body dir="rtl">
    <div class="container my-5">
        <h1>نظام إدارة الصالة الرياضية</h1>
        <br>

        <!-- Stats Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card p-3 stats-card text-center">
                    <h5>عدد المستخدمين</h5>
                    <h2><?= count($users) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 stats-card text-center">
                    <h5>المستخدمون النشطون</h5>
                    <h2><?= count(array_filter($users, function ($u) {
                            return $u['status'] === 'نشط';
                        })); ?>
                    </h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 stats-card text-center">
                    <h5>المستخدمون المعلقون</h5>
                    <h2><?= count(array_filter($users, function ($u) {
                            return $u['status'] === 'معلق';
                        })); ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Add User Form -->
        <div class="card p-4 mb-4">
            <h4 class="text-center mb-3">إضافة مستخدم جديد</h4>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="name" class="form-control" placeholder="اسم المستخدم" required>
                    </div>
                    <div class="col-md-6">
                        <input type="date" name="subscription" class="form-control" required>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-custom">إضافة مستخدم</button>
                </div>
            </form>
        </div>

        <!-- User List -->
        <div class="card p-4">
            <h4 class="text-center mb-3">قائمة المستخدمين</h4>

            <!-- Search Box -->
            <div class="search-box text-center">
                <input type="text" id="searchInput" class="form-control" placeholder="بحث عن مستخدم" onkeyup="searchUsers()">
            </div>

            <table class="table table-hover text-center" id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>الاسم</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $index => $user): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= $user['expiration'] ?></td>
                            <td>
                                <span class="badge <?= $user['status'] === 'نشط' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $user['status'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button class="btn btn-no-frame"><span class="material-icons">edit_square</span></button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button class="btn btn-no-frame"><span class="material-icons">pause_circle</span></button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="index" value="<?= $index ?>">
                                    <button class="btn btn-no-frame"><span class="material-icons">delete_sweep</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Export Excel Button -->
            <div class="text-center mt-3">
                <button class="btn btn-outline-custom" onclick="exportToExcel()">تصدير إلى Excel</button>
            </div>
        </div>
    </div>

    <script>
        // Function to filter users based on search input
        function searchUsers() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let table = document.getElementById('userTable');
            let tr = table.getElementsByTagName('tr');
            for (let i = 0; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toLowerCase().indexOf(input) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        // Function to export table data to Excel
        function exportToExcel() {
            let table = document.getElementById('userTable');
            let rows = table.rows;
            let csv = [];
            for (let i = 0; i < rows.length; i++) {
                let row = rows[i];
                let cols = row.cells;
                let rowData = [];
                for (let j = 0; j < cols.length - 1; j++) {
                    rowData.push(cols[j].innerText);
                }
                csv.push(rowData.join(","));
            }
            let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "users_data.csv");
            link.click();
        }

        function editUser(index) {
            alert("Edit functionality is not yet implemented for user at index " + index);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
