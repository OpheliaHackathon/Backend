<?php

header('Content-Type: application/json');

include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

try {
    // Legge il JSON ricevuto
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Verifica se il JSON è stato decodificato correttamente
    if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Errore nella codifica dei dati inseriti nel JSON.');
    }

    // Controllo sui dati ricevuti
    if (!isset($data['username'], $data['password'])) {
        throw new Exception('I dati richiesti (username, password) sono mancanti.');
    }

    $username = trim($data['username']);
    $password = $data['password'];

    // Query per selezionare la password in base all'username
    $query = "SELECT password, username FROM Utente WHERE username = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query: ' . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception('Username non trovato.');
    }

    // Recupero della password hashata dal database
    $stmt->bind_result($password_database, $username_database);
    $stmt->fetch();

    // Verifica della password
    if (!password_verify($password, $password_database)) {
        throw new Exception('Password errata.');
    }

    // Se la password è corretta, genera il token
    $token = generateToken();

    // Inserisce il token nel database
    $query = "INSERT INTO Token (token, username) VALUES (?, ?)";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query per il token: ' . $conn->error);
    }

    $stmt->bind_param("ss", $token, $username);
    $stmt->execute();

    // Risposta di successo
    echo json_encode([
        'success' => true,
        'token' => $token,
    ]);

} catch (Exception $e) {
    // Gestione degli errori con messaggi chiari
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
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