
<?php
// Database configuration
$servername = "localhost";
$username = "root";  // Or your actual username
$password = "";      // Or your actual password
$dbname = "user_registration"; // Or the actual name!

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);  // This will now show the EXACT error.
}
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $login = trim($_POST["login"]);
    $firstName = trim($_POST["first_name"]);
    $lastName = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $birthdate = $_POST["birthdate"];
    $city = $_POST["city"];
    $gender = $_POST["gender"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    $agreeTerms = isset($_POST["agree_terms"]);

    // Validation
    if (empty($login)) {
        $errors[] = "Логин обязателен для заполнения.";
    }
    if (empty($firstName)) {
        $errors[] = "Имя обязательно для заполнения.";
    }
    if (empty($lastName)) {
        $errors[] = "Фамилия обязательна для заполнения.";
    }
    if (empty($email)) {
        $errors[] = "Электронная почта обязательна для заполнения.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат электронной почты.";
    }
    if (empty($phone)) {
        $errors[] = "Номер телефона обязателен для заполнения.";
    } elseif (!preg_match("/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/", $phone)) {
        $errors[] = "Некорректный формат номера телефона. Используйте +7(XXX)XXX-XX-XX.";
    }
    if (empty($birthdate)) {
        $errors[] = "Дата рождения обязательна для заполнения.";
    }
    if (empty($city)) {
        $errors[] = "Город проживания обязателен для заполнения.";
    }
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Пароль должен содержать не менее 8 символов.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы одну заглавную букву.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Пароль должен содержать хотя бы один специальный символ.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Пароли не совпадают.";
    }
    if (!$agreeTerms) {
        $errors[] = "Необходимо согласиться на обработку персональных данных.";
    }

    // Check for unique login, email, and phone
    $stmt = $conn->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Этот логин уже используется.";
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Эта электронная почта уже используется.";
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Этот номер телефона уже используется.";
    }
    $stmt->close();

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert data into the database
        $stmt = $conn->prepare("INSERT INTO users (login, first_name, last_name, email, phone, birthdate, city, gender, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $login, $firstName, $lastName, $email, $phone, $birthdate, $city, $gender, $hashedPassword);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Ошибка при регистрации: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Регистрация</title>
    <style>
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        /* Basic form styling (improve as needed) */
        form {
            width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            box-sizing: border-box; /* Important for width */
        }

        input[type="radio"] {
            margin-right: 5px;
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

        .success-message {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h1>Регистрация</h1>

<?php if (!empty($errors)): ?>
    <div class="error-container">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message">
        Регистрация прошла успешно! <a href="login.php">Войти</a>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="login">Логин:</label>
    <input type="text" id="login" name="login" value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>

    <label for="first_name">Имя:</label>
    <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>

    <label for="last_name">Фамилия:</label>
    <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>

    <label for="email">Электронная почта:</label>
    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>

    <label for="phone">Номер телефона (+7(XXX)XXX-XX-XX):</label>
    <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>

    <label for="birthdate">Дата рождения:</label>
    <input type="date" id="birthdate" name="birthdate" value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>" required>

    <label for="city">Город проживания:</label>
    <select id="city" name="city" required>
        <option value="">Выберите город</option>
        <option value="Москва" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Москва') ? 'selected' : ''; ?>>Москва</option>
        <option value="Санкт-Петербург" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Санкт-Петербург') ? 'selected' : ''; ?>>Санкт-Петербург</option>
        <option value="Екатеринбург" <?php echo (isset($_POST['city']) && $_POST['city'] == 'Екатеринбург') ? 'selected' : ''; ?>>Екатеринбург</option>
        <!-- Add more cities as needed -->
    </select>

    <label>Пол:</label>
    <input type="radio" id="male" name="gender" value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'checked' : ''; ?> required>
    <label for="male">Мужской</label>
    <input type="radio" id="female" name="gender" value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'checked' : ''; ?> required>
    <label for="female">Женский</label>

    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm_password">Подтверждение пароля:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <label for="agree_terms">
        <input type="checkbox" id="agree_terms" name="agree_terms" required>
        Согласие на обработку персональных данных
    </label>

    <button type="submit">Зарегистрироваться</button>
</form>

</body>
</html>
