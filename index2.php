<?php
include('../db_connect.php');

// Fetch all LGAs
$lga_query = $conn->query("SELECT lga_id, lga_name FROM lga ORDER BY lga_name");

$result_output = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lga_id'])) {
    $lga_id = (int) $_POST['lga_id'];

    if ($lga_id > 0) {
        $sql = "
            SELECT apr.party_abbreviation, SUM(apr.party_score) AS total_score
            FROM announced_pu_results apr
            INNER JOIN polling_unit pu ON apr.polling_unit_id = pu.polling_unit_id
            WHERE pu.lga_id = ?
            GROUP BY apr.party_abbreviation
            ORDER BY total_score DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $lga_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $nameStmt = $conn->prepare("SELECT lga_name FROM lga WHERE lga_id = ?");
            $nameStmt->bind_param("i", $lga_id);
            $nameStmt->execute();
            $lga_name = $nameStmt->get_result()->fetch_assoc()['lga_name'] ?? 'Selected LGA';
            $nameStmt->close();

            $result_output .= "<h3>Total Votes by Party for <u>$lga_name</u></h3>";
            $result_output .= "<table><thead><tr><th>Party</th><th>Total Votes</th></tr></thead><tbody>";

            while ($row = $res->fetch_assoc()) {
                $result_output .= "<tr><td>{$row['party_abbreviation']}</td><td>" . number_format($row['total_score']) . "</td></tr>";
            }

            $result_output .= "</tbody></table>";
        } else {
            $message = "<div class='error'>⚠️ No results found for this LGA.</div>";
        }

        $stmt->close();
    } else {
        $message = "<div class='error'>⚠️ Please select a valid LGA.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>LGA Total Results Viewer</title>
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f2f4f8;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    h2 {
      text-align: center;
      color: #157C57;
      margin-bottom: 20px;
    }
    form {
      text-align: center;
      margin-bottom: 25px;
    }
    select, button {
      padding: 10px 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    button {
      background-color: #157C57;
      color: white;
      border: none;
      cursor: pointer;
      transition: 0.3s;
    }
    button:hover {
      background-color: #0f5c40;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th {
      background-color: #157C57;
      color: white;
      padding: 10px;
      text-align: center;
    }
    td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .error {
      background: #f8d7da;
      color: #721c24;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      text-align: center;
    }
    .footer {
      text-align: center;
      margin-top: 30px;
      font-size: 14px;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>LGA Total Result Viewer</h2>

    <form method="POST">
      <label for="lga_id"><strong>Select Local Government:</strong></label><br><br>
      <select name="lga_id" required>
        <option value="">-- Choose LGA --</option>
        <?php while($lga = $lga_query->fetch_assoc()) { ?>
          <option value="<?php echo $lga['lga_id']; ?>" <?php if(isset($lga_id) && $lga_id == $lga['lga_id']) echo 'selected'; ?>>
            <?php echo htmlspecialchars($lga['lga_name']); ?>
          </option>
        <?php } ?>
      </select>
      <button type="submit">View Total Results</button>
    </form>

    <?php
      echo $message;
      echo $result_output;
    ?>

    <div class="footer">
      <p>Tip: Select an LGA to view total votes per party.</p>
    </div>
  </div>
</body>
</html>
