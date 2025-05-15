<?php
session_start();

// Database connection details
$host = "localhost";
$username = "your_username"; // Replace with your database username
$password = "your_password"; // Replace with your database password
$database = "user_registration";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST["login"]);
    $password = $_POST["password"];

    if (empty($login)) {
        $errors[] = "Логин обязателен для заполнения.";
    }
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                // Password is correct, start a new session
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $row["id"];
                $_SESSION["login"] = $login;

                // Redirect to a welcome page
                header("location: welcome.php");
                exit;
            } else {
                $errors[] = "Неверный пароль.";
            }
        } else {
            $errors[] = "Пользователь с таким логином не найден.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Авторизация</title>
    <style>
        .error {
            color: red;
        }

        form {
            width: 300px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #3e8e41;
        }

        .error-container {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h1>Авторизация</h1>

<?php if (!empty($errors)): ?>
    <div class="error-container">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="login">Логин:</label>
    <input type="text" id="login" name="login" required>

    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Войти</button>
</form>

<p>Еще не зарегистрированы? <a href="register.php">Зарегистрироваться</a></p>

</body>
</html>
