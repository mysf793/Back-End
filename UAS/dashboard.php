<?php
session_start();
ob_start();  // Start output buffering

include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
include 'functions.php';
$user_data = getUserData($user_id);
$role = $user_data['role'];

// Admin - Add/Delete Mahasiswa
if ($role == 'admin' && isset($_POST['add_mahasiswa'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = 'mahasiswa';

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    $stmt->execute();
    echo "<div class='alert alert-success'>Mahasiswa berhasil ditambahkan!</div>";
}

if ($role == 'admin' && isset($_POST['delete_mahasiswa'])) {
    $mahasiswa_id = $_POST['mahasiswa_id'];

    // Pastikan bahwa yang dihapus adalah mahasiswa (bukan admin)
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $mahasiswa_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_role);
    $stmt->fetch();

    // Hanya mahasiswa yang boleh dihapus
    if ($user_role == 'mahasiswa') {
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $mahasiswa_id);
        $delete_stmt->execute();
        echo "<div class='alert alert-success'>Mahasiswa berhasil dihapus!</div>";
    } else {
        echo "<div class='alert alert-danger'>Tidak dapat menghapus user dengan role selain mahasiswa!</div>";
    }
}

$searchQuery = '';
if ($role == 'admin' && isset($_POST['search'])) {
    $searchQuery = $_POST['searchQuery'];
    $sql = "SELECT * FROM users WHERE username LIKE '%$searchQuery%' AND role = 'mahasiswa'";
} else {
    $sql = "SELECT * FROM users WHERE role = 'mahasiswa'";
}

$users = $conn->query($sql);

// Ambil daftar mata kuliah dari database
$mata_kuliah_result = $conn->query("SELECT * FROM mata_kuliah");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 40px 20px;
        }

        h2, h3 {
            color: #343a40;
            margin-bottom: 30px;
        }

        table {
            margin-top: 20px;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            border-radius: 25px;
            padding: 10px 20px;
            margin-top: 10px;
        }

        .btn-custom:hover {
            background-color: #0056b3;
        }

        .form-control {
            border-radius: 25px;
        }

        .alert {
            border-radius: 10px;
            padding: 10px;
        }

        .search-bar {
            max-width: 400px;
            margin-bottom: 30px;
        }

        .card {
            margin-top: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 20px;
        }

        .card-body {
            background-color: #f8f9fa;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if ($role == 'admin'): ?>
        <!-- Admin Dashboard -->
        <div class="card">
            <div class="card-header">Admin Dashboard</div>
            <div class="card-body">
                <form method="POST" class="text-center mb-4 search-bar">
                    <input type="text" name="searchQuery" class="form-control d-inline-block w-75" placeholder="Search mahasiswa..." value="<?= htmlspecialchars($searchQuery) ?>">
                    <button type="submit" name="search" class="btn btn-custom">Search</button>
                </form>

                <h3>Tambah Mahasiswa</h3>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" name="add_mahasiswa" class="btn btn-custom">Tambah Mahasiswa</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['role']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="mahasiswa_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_mahasiswa" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus mahasiswa ini?');">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php elseif ($role == 'dosen'): ?>
        <!-- Dosen Dashboard -->
        <div class="card">
            <div class="card-header">Dosen Dashboard</div>
            <div class="card-body">
                <h3>Input Nilai Mahasiswa</h3>

                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label for="mata_kuliah" class="form-label">Pilih Mata Kuliah</label>
                        <select name="mata_kuliah_id" class="form-control" required>
                            <option value="">Pilih Mata Kuliah</option>
                            <?php while ($mata_kuliah = $mata_kuliah_result->fetch_assoc()): ?>
                                <option value="<?= $mata_kuliah['id'] ?>"><?= htmlspecialchars($mata_kuliah['nama_mata_kuliah']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="pilih_mata_kuliah" class="btn btn-custom">Pilih</button>
                </form>

                <?php
                if (isset($_POST['pilih_mata_kuliah'])) {
                    $mata_kuliah_id = $_POST['mata_kuliah_id'];
                    $_SESSION['mata_kuliah_id'] = $mata_kuliah_id;
                }

                if (isset($_SESSION['mata_kuliah_id'])):
                    $mata_kuliah_id = $_SESSION['mata_kuliah_id'];
                    echo "<h4>Mata Kuliah yang Dipilih: " . htmlspecialchars($mata_kuliah_id) . "</h4>";
                ?>

                <!-- Form input nilai -->
                <form method="POST">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    <th>Kehadiran</th>
                                    <th>UTS</th>
                                    <th>UAS</th>
                                    <th>Responsi</th>
                                    <th>Praktikum</th>
                                    <th>Rata-Rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $conn->query("SELECT id, username FROM users WHERE role = 'mahasiswa'");
                                while ($row = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><input type="number" name="kehadiran[<?= $row['id'] ?>]" class="form-control" step="0.01" required></td>
                                        <td><input type="number" name="uts[<?= $row['id'] ?>]" class="form-control" step="0.01" required></td>
                                        <td><input type="number" name="uas[<?= $row['id'] ?>]" class="form-control" step="0.01" required></td>
                                        <td><input type="number" name="responsi[<?= $row['id'] ?>]" class="form-control" step="0.01" required></td>
                                        <td><input type="number" name="praktikum[<?= $row['id'] ?>]" class="form-control" step="0.01" required></td>
                                        <td></td> <!-- Rata-rata dihitung setelah submit -->
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" name="input_nilai" class="btn btn-custom">Input Nilai</button>
                </form>

                <form method="POST" class="mt-4">
                    <button type="submit" name="view_nilai" class="btn btn-secondary">Lihat Nilai Mahasiswa</button>
                </form>

                <?php
                if (isset($_POST['view_nilai'])) {
                    $mata_kuliah_id = $_SESSION['mata_kuliah_id'];
                    echo "<h4>Nilai Mahasiswa untuk Mata Kuliah ID: $mata_kuliah_id</h4>";

                    $nilai_result = $conn->query("SELECT nm.mahasiswa_id, u.username, nm.kehadiran, nm.uts, nm.uas, nm.responsi, nm.praktikum 
                                                  FROM nilai_mahasiswa nm
                                                  JOIN users u ON nm.mahasiswa_id = u.id
                                                  WHERE nm.mata_kuliah_id = $mata_kuliah_id");
                    if ($nilai_result->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Mahasiswa</th>
                                <th>Kehadiran</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Responsi</th>
                                <th>Praktikum</th>
                                <th>Rata-Rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($nilai_row = $nilai_result->fetch_assoc()):
                                $rata_rata = ($nilai_row['kehadiran'] + $nilai_row['uts'] + $nilai_row['uas'] + $nilai_row['responsi'] + $nilai_row['praktikum']) / 5;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($nilai_row['username']) ?></td>
                                    <td><?= $nilai_row['kehadiran'] ?></td>
                                    <td><?= $nilai_row['uts'] ?></td>
                                    <td><?= $nilai_row['uas'] ?></td>
                                    <td><?= $nilai_row['responsi'] ?></td>
                                    <td><?= $nilai_row['praktikum'] ?></td>
                                    <td><?= number_format($rata_rata, 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                    else:
                        echo "<div class='alert alert-warning'>Belum ada nilai yang dimasukkan untuk mata kuliah ini.</div>";
                    endif;
                }
                endif;
                ?>
            </div>
        </div>
    <?php elseif ($role == 'mahasiswa'): ?>
        <!-- Mahasiswa Dashboard -->
        <div class="card">
            <div class="card-header">Mahasiswa Dashboard</div>
            <div class="card-body">
                <h2>Nilai Anda</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Mata Kuliah</th>
                                <th>Kehadiran</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Responsi</th>
                                <th>Praktikum</th>
                                <th>Rata-Rata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $mahasiswa_id = $_SESSION['user_id'];
                            $nilai_result = $conn->query("SELECT nm.mata_kuliah_id, mk.nama_mata_kuliah, nm.kehadiran, nm.uts, nm.uas, nm.responsi, nm.praktikum 
                                                         FROM nilai_mahasiswa nm
                                                         JOIN mata_kuliah mk ON nm.mata_kuliah_id = mk.id
                                                         WHERE nm.mahasiswa_id = $mahasiswa_id");
                            while ($nilai_row = $nilai_result->fetch_assoc()):
                                $rata_rata = ($nilai_row['kehadiran'] + $nilai_row['uts'] + $nilai_row['uas'] + $nilai_row['responsi'] + $nilai_row['praktikum']) / 5;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($nilai_row['nama_mata_kuliah']) ?></td>
                                    <td><?= $nilai_row['kehadiran'] ?></td>
                                    <td><?= $nilai_row['uts'] ?></td>
                                    <td><?= $nilai_row['uas'] ?></td>
                                    <td><?= $nilai_row['responsi'] ?></td>
                                    <td><?= $nilai_row['praktikum'] ?></td>
                                    <td><?= number_format($rata_rata, 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
ob_end_flush(); // End output buffering
?>
