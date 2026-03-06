<?php
session_start();

if (isset($_POST['reset'])) {
  $_SESSION['validated_flags'] = [];
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
}

$conn = mysqli_connect("13.61.151.19", "ctf_user", "MyP@ssw0rd!", "ctf_db");
$output = "";

if (!isset($_SESSION['validated_flags'])) {
  $_SESSION['validated_flags'] = [];
}

if (isset($_POST['flag']) && !empty(trim($_POST['flag']))) {
  $flag = $_POST['flag'];
  $stmt = mysqli_prepare($conn, "SELECT id, flag_value FROM flags_validation WHERE flag_value = ?");
  mysqli_stmt_bind_param($stmt, "s", $flag);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if (!isset($_SESSION['validated_flags'][$row["flag_value"]])) {
      $_SESSION['validated_flags'][$row["flag_value"]] = [
        'id' => $row['id'],
        'flag' => $row['flag_value']
      ];
      $output = "Flag validated successfully!";
    } else {
      $output = "Flag already validated.";
    }
    mysqli_free_result($result);
  } else {
    $output = "Flag not found.";
  }
  mysqli_stmt_close($stmt);
}
?>
<html>

<head>
  <link rel="stylesheet" href="style.css">
  <link rel="icon"
    href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👨‍💻</text></svg>">
  <title>SEC-LAB CTF</title>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
</head>

<body class="validations">
  <div class="box">
    <h2>CTF Validations</h2>
    <p>Welcome to the CTF validations page!</p>
  </div>

  <div class="box">
    <form method="POST">
      <input type="text" name="flag" placeholder="Enter your flag for validation">
      <button type="submit">Validate</button>
    </form>
  </div>

  <div class="box-max">
    <?php if (empty($_SESSION['validated_flags'])): ?>
      <p>No flags found for now.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>ID</th>
          <th>Flag</th>
          <th>Status</th>
        </tr>
        <?php foreach ($_SESSION['validated_flags'] as $validated_flag): ?>
          <tr>
            <td><?php echo htmlspecialchars($validated_flag['id']); ?></td>
            <td><?php echo htmlspecialchars($validated_flag['flag']); ?></td>
            <td>
              <span class="status success">VALIDATED</span>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
    <?php if (!empty($_SESSION['validated_flags'])): ?>
      <div class="clear-form">
        <form method="POST">
          <button type="submit" name="reset">Clear Validated Flags</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div id="resultModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <span class="close-btn">&times;</span>
        <h2>Validation Result</h2>
      </div>
      <p id="modalOutputText"></p>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('resultModal');
      const modalOutputText = document.getElementById('modalOutputText');
      const phpOutput = "<?php echo $output; ?>";
      const closeBtn = document.querySelector('#resultModal .close-btn');

      if (phpOutput.trim()) {
        modalOutputText.textContent = phpOutput;
        modal.style.display = 'block';

        if (phpOutput.includes("success")) {
          confetti({
            particleCount: 100,
            spread: 70,
            origin: {
              y: 0.6
            },
            zIndex: 1001
          });
        }
      }

      closeBtn.onclick = function() {
        modal.style.display = 'none';
      }

      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = 'none';
        }
      }
    });
  </script>
</body>

</html>