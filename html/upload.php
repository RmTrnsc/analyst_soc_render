<?php
if (isset($_FILES['file'])) {
    $target = "uploads/" . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        echo "File uploaded to: " . $target;
    } else {
        echo "Upload failed.";
    }
}
?>
<html>

<head>
    <link rel="stylesheet" href="style.css">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👨‍💻</text></svg>">
    <title>SEC-LAB CTF</title>
</head>

<body>
    <div class="box">
        <h2>Member Profile Picture Update</h2>
        <form enctype="multipart/form-data" method="POST">
            <input type="file" name="file">
            <button type="submit">Upload</button>
        </form>
    </div>
</body>

</html>