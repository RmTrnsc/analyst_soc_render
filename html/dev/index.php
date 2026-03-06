<?php
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect("13.61.151.19", "ctf_user", "MyP@ssw0rd!", "ctf_db");
$output = "";

if (isset($_POST['user'])) {
    $output = "";
    $user = $_POST['user'];
    $query = "SELECT flag FROM flags_table WHERE username = '$user'";

    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                if (strpos($row['flag'], 'FLAG_2') !== false) {
                    $output .= "<span style='color: #39ff14;'>[!] EXPLOIT SUCCESS: " . $row["flag"] . "</span><br>";
                } else {
                    $output .= "System Message: " . $row["flag"] . "<br>";
                }
            }
        } else {
            $output = "User not found.";
        }
    } else {
        $output = "<span style='color: #ff3914;'>[!] DATABASE ERROR: " . mysqli_error($conn) . "</span>";
    }
}
?>
<html>

<head>
    <link rel="stylesheet" href="../../html/style.css">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👨‍💻</text></svg>">
    <title>SEC-LAB CTF</title>
</head>

<body>
    <div class="box">
        <h2>Internal Dev Login</h2>
        <form method="POST">
            <input type="text" name="user" placeholder="Username">
            <button type="submit">Login</button>
        </form>
        <p style="color: red;"><?php echo $output; ?></p>
    </div>
</body>

</html>