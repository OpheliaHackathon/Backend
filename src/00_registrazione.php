<?php
include 'Handler/connessione_database.php';

header('Content-Type: application/json');

try {
    // Legge il JSON ricevuto
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    // Verifica che il JSON sia stato decodificato correttamente
    if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Errore nella codifica dei dati inseriti nel JSON.');
    }

    // Verifica che i dati siano presenti
    if (!isset($data['username'], $data['password'], $data['confirmPassword'], $data['email'])) {
        throw new Exception('Dati mancanti: username, password, conferma password o email.');
    }

    // Controllo username con regex
    $username = trim($data['username']);
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception('Username non valido. Deve contenere solo lettere, numeri e underscore.');
    }

    // Controllo password con regex
    $password = $data['password'];
    $confirmPassword = $data['confirmPassword'];
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/', $password)) {
        throw new Exception('Password non valida. Deve contenere almeno una lettera maiuscola, una minuscola, un numero e un carattere speciale.');
    }

    // Verifica che le password coincidano
    if ($password !== $confirmPassword) {
        throw new Exception('Le password non coincidono.');
    }

    // Controllo email con regex
    $email = trim($data['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email non valida.');
    }

    // Controllo se l'username esiste già nel database
    $query = "SELECT COUNT(*) FROM `Utente` WHERE `username` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($username_count);
    $stmt->fetch();
    $stmt->close();

    if ($username_count > 0) {
        throw new Exception('Username già esistente.');
    }

    // Controllo se l'email esiste già nel database
    $query = "SELECT COUNT(*) FROM `Utente` WHERE `email` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($email_count);
    $stmt->fetch();
    $stmt->close();

    if ($email_count > 0) {
        throw new Exception('Email già registrata.');
    }

    // Hash della password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Query per l'inserimento
    $query = "INSERT INTO `Utente` (`username`, `password`, `email`) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query: ' . $conn->error);
    }

    // Associa i parametri
    $stmt->bind_param("sss", $username, $password_hash, $email);

    if (!$stmt->execute()) {
        throw new Exception('Errore! Username o email già registrati.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registrazione avvenuta con successo'
    ]);

} catch (Exception $e) {
    // Gestione degli errori
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Chiusura della connessione
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>