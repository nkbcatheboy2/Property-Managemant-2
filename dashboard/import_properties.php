<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_role(['Admin', 'Property Officer']);

$allowed_categories = get_user_allowed_categories();
$error = '';
$imported = 0;
$skipped = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        $error = "Sirf .csv file upload karein. Excel file ko 'Save As -> CSV' karke upload karein.";
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');

        if ($handle === false) {
            $error = "The file could not be read.";
        } else {
            $header = fgetcsv($handle); // pehli row = column names
            $header = array_map(static fn($value) => strtolower(trim($value)), $header ?: []);
            $row_num = 1;

            $stmt = $pdo->prepare(
                "INSERT INTO properties (scheme_name, scheme_address, property_no, property_code, address, area_size, property_type, allotment_date, price, category, added_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $check = $pdo->prepare("SELECT id FROM properties WHERE property_code = ?");

            while (($row = fgetcsv($handle)) !== false) {
                $row_num++;

                if (count($row) < 7) {
                    $skipped[] = "Row $row_num: columns kam hain";
                    continue;
                }

                $values = array_pad($row, count($header), '');
                $record = array_combine($header, $values);
                $scheme_name = $record['scheme_name'] ?? ($row[0] ?? '');
                $property_no = $record['property_no'] ?? ($row[1] ?? '');
                $property_code = $record['property_code'] ?? ($row[2] ?? '');
                $address = $record['address'] ?? ($row[3] ?? '');
                $scheme_address = $record['scheme_address'] ?? $address;
                $area_size = $record['area_size'] ?? ($row[4] ?? '');
                $property_type = $record['property_type'] ?? 'Residential';
                $allotment_date = trim($record['allotment_date'] ?? '') ?: null;
                $price = $record['price'] ?? ($row[5] ?? '');
                $category = $record['category'] ?? ($row[6] ?? '');
                $property_code = trim($property_code);
                if ($property_code === '') {
                    do {
                        $property_code = (string) random_int(10000, 99999);
                        $id_check = $pdo->prepare("SELECT id FROM properties WHERE property_code = ?");
                        $id_check->execute([$property_code]);
                    } while ($id_check->fetch());
                }
                $category = trim($category);

                if (trim($scheme_name) === '' || trim($scheme_address) === '' || trim($price) === '' || trim($area_size) === '') {
                    $skipped[] = "Row $row_num: a required field is empty";
                    continue;
                }

                if (!in_array(trim($property_type), ['Residential', 'Commercial', 'Shop', 'Office', 'Plot', 'Flat'], true)) {
                    $skipped[] = "Row $row_num: invalid property type";
                    continue;
                }

                if (!in_array($category, $allowed_categories)) {
                    $skipped[] = "Row $row_num: you do not have permission for the '$category' category";
                    continue;
                }

                $check->execute([trim($property_code)]);
                if ($check->rowCount() > 0) {
                    $skipped[] = "Row $row_num: Property ID '$property_code' pehle se maujood hai";
                    continue;
                }

                $stmt->execute([
                    trim($scheme_name), trim($scheme_address), trim($property_no), trim($property_code), trim($address),
                    trim($area_size), trim($property_type), $allotment_date, trim($price), $category, $_SESSION['user_id']
                ]);
                $imported++;
            }

            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Properties</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Import Properties (Excel/CSV)</span>
    <div class="text-white">
        <a href="properties.php" class="text-white">← Back to List</a>
        | <a href="../logout.php" class="text-white">Logout</a>
    </div>
</nav>

<div class="container mt-4" style="max-width: 700px;">

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($imported > 0 || !empty($skipped)): ?>
        <div class="alert alert-info">
            <strong><?= $imported ?></strong> properties successfully import ho gayi.
            <?php if (!empty($skipped)): ?>
                <br><strong><?= count($skipped) ?></strong> rows skip ho gayi:
                <ul class="mb-0">
                    <?php foreach ($skipped as $s): ?><li><?= htmlspecialchars($s) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card p-4">
        <h5 class="mb-3">Upload CSV File</h5>
        <p class="text-muted">
            Excel file (.xlsx) ko pehle <strong>"Save As" &rarr; CSV (Comma delimited)</strong> format me save kar lein, phir yahan upload karein.
        </p>

        <p>
            <a href="../assets/sample_property_template.csv" class="btn btn-outline-secondary btn-sm" download>
                Download Sample Template
            </a>
        </p>

        <div class="alert alert-light border">
            Columns order (pehli row header honi chahiye):<br>
            <code>scheme_name, scheme_address, property_no, property_code, address, area_size, property_type, allotment_date, price, category</code><br>
            Category values sirf ye ho sakti hain: <strong><?= implode(', ', $allowed_categories) ?></strong>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Upload & Import</button>
        </form>
    </div>
</div>

</body>
</html>
