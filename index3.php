<?php

include('../db_connect.php'); 

$message = "";
$result_output = "";


$polling_units = $conn->query("SELECT polling_unit_id, polling_unit_name FROM polling_unit ORDER BY polling_unit_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $polling_unit_id = isset($_POST['polling_unit_id']) ? (int) $_POST['polling_unit_id'] : 0;
    $party_abbr = $_POST['party_abbreviation'] ?? [];
    $party_score = $_POST['party_score'] ?? [];

  
    $valid_rows = 0;
    for ($i = 0; $i < count($party_abbr); $i++) {
        if (trim($party_abbr[$i]) !== "") {
            $valid_rows++;
        }
    }

    if ($polling_unit_id > 0 && $valid_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO announced_pu_results (polling_unit_id, party_abbreviation, party_score) VALUES (?, ?, ?)");
        if (!$stmt) {
            $message = "<div class='error'>Database error: failed to prepare insert statement.</div>";
        } else {
            $success = true;
            for ($i = 0; $i < count($party_abbr); $i++) {
                $abbr = trim($party_abbr[$i]);
                $score = isset($party_score[$i]) && is_numeric($party_score[$i]) ? (int)$party_score[$i] : 0;

                if ($abbr !== "") {
                    $stmt->bind_param("isi", $polling_unit_id, $abbr, $score);
                    if (!$stmt->execute()) {
                        $success = false;
                        break;
                    }
                }
            }

            if ($success) {
                $puName = "Selected Polling Unit";
                $puRes = $conn->prepare("SELECT polling_unit_name FROM polling_unit WHERE polling_unit_id = ?");
                if ($puRes) {
                    $puRes->bind_param("i", $polling_unit_id);
                    $puRes->execute();
                    $r = $puRes->get_result()->fetch_assoc();
                    if ($r && isset($r['polling_unit_name'])) $puName = $r['polling_unit_name'];
                    $puRes->close();
                }

                $message = "<div class='success'>✅ Results added successfully for <strong>" . htmlspecialchars($puName) . "</strong>!</div>";
                
                $message .= "<p><a href=\"../q1/index.php\" target=\"_blank\">Open Polling Unit Results Viewer</a> — select the same polling unit to view newly added rows.</p>";
            } else {
                $message = "<div class='error'>❌ Something went wrong while inserting results. Please try again.</div>";
            }

            $stmt->close();
        }
    } else {
        $message = "<div class='error'>⚠️ Please select a polling unit and provide at least one party name with a score.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add New Polling Unit Results</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; background: #f7f7f7; }
    .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); max-width:800px; margin:auto; }
    input, select, button { padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; }
    .fullwidth { width: 100%; box-sizing: border-box; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 8px; text-align: left; border-bottom:1px solid #eee; }
    .add-row { margin-top: 10px; background: #28a745; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    .remove-row { background: #dc3545; color: #fff; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
    .success { background: #d4edda; color: #155724; padding: 10px; margin-top: 10px; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin-top: 10px; border-radius: 5px; }
    a { color: #007BFF; text-decoration: none; }
  </style>
  <script>
  
    function addRow() {
      const table = document.getElementById("partyTable");
      const newRow = table.insertRow();
      newRow.innerHTML = `
        <td><input type="text" name="party_abbreviation[]" placeholder="Party (e.g. PDP)" required class="fullwidth"></td>
        <td><input type="number" name="party_score[]" placeholder="Score" value="0" required class="fullwidth"></td>
        <td><button type="button" class="remove-row" onclick="removeRow(this)">Remove</button></td>
      `;
    }

    function removeRow(button) {
      const row = button.parentElement.parentElement;
      row.parentElement.removeChild(row);
    }
  </script>
</head>
<body>
  <h2 style="text-align:center;">Add New Polling Unit Results</h2>

  <div class="container">
    <form method="POST" action="">
      <label for="polling_unit_id">Select Polling Unit:</label>
      <select name="polling_unit_id" required class="fullwidth">
        <option value="">-- Choose Polling Unit --</option>
        <?php
        if ($polling_units && $polling_units->num_rows) {
            while($pu = $polling_units->fetch_assoc()) {
                echo '<option value="'.(int)$pu['polling_unit_id'].'">'.htmlspecialchars($pu['polling_unit_name']).'</option>';
            }
        }
        ?>
      </select>

      <h3>Enter Party Results</h3>
      <table id="partyTable">
        <tr>
          <th style="width:60%;">Party</th>
          <th style="width:25%;">Score</th>
          <th style="width:15%;">Action</th>
        </tr>
        <tr>
          <td><input type="text" name="party_abbreviation[]" placeholder="Party (e.g. PDP)" required class="fullwidth"></td>
          <td><input type="number" name="party_score[]" placeholder="Score" value="0" required class="fullwidth"></td>
          <td><button type="button" class="remove-row" onclick="removeRow(this)">Remove</button></td>
        </tr>
      </table>

      <button type="button" class="add-row" onclick="addRow()">+ Add Another Party</button>
      <br><br>
      <button type="submit">Save Results</button>
    </form>

    <?php
      echo $message;
    ?>
  </div>
</body>
</html>
