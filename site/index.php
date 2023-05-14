<?php
#echo $_ENV["MYSQL_ROOT_PASSWORD"];
$conn = new mysqli("mydb-service", "root", $_ENV["MYSQL_ROOT_PASSWORD"], "db");
// Check connection
if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE user (
id INT,
name VARCHAR(255)
)";

$sql = "SHOW TABLES LIKE 'user'";
$result = mysqli_query($conn, $sql);

if (!mysqli_num_rows($result) == 1) {
   
    // Création de la table "user"
    $sql = "CREATE TABLE user (
    id INT,
    name VARCHAR(255)
    )";

    if (mysqli_query($conn, $sql)) {
        echo "Table user created successfully";
    } else {
        echo "Error creating table: " . mysqli_error($conn);
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $sql = "INSERT INTO user (name) VALUES ('$name')";
        if ($conn->query($sql) === TRUE) {
                // Success
                header("Location: ".$_SERVER['PHP_SELF']);
                die();
        } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
        }
}


$sql = "SELECT name FROM user";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
        <title>My page</title>
</head>
<body>
        <h1>Users</h1>
        <?php
        if ($result->num_rows > 0) {
                // output data of each row
                while($row = $result->fetch_assoc()) {
                        echo $row['name']."<br>";
                }
        } else {
                echo "0 results";
        }
        ?>

        <h2>Add User</h2>
        <form method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name"><br>
                <button type="submit">Add</button>
        </form>
</body>
</html>

<?php
$conn->close();
?>
