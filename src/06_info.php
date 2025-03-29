<?php
include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

header('Content-Type: application/json');

try {
    // Legge il token dall'header
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';

    // Verifica il token e ottiene l'username
    $username = verifyToken($token);

    // Se l'username è valido
    if ($username) {
        // Prepara la query per ottenere l'email dell'utente
        $query = "SELECT email FROM Utente WHERE username = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            throw new Exception('Errore nella preparazione della query: ' . $conn->error);
        }

        // Associa i parametri e esegue la query
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($email);
            $stmt->fetch();

            // Restituisce il risultato in formato JSON
            echo json_encode([
                'success' => true,
                'username' => $username,
                'email' => $email
            ]);
        } else {
            throw new Exception('Nessun utente trovato con questo username.');
        }
    } else {
        throw new Exception('Errore nella validazione del token, token non presente nel database.');
    }
} catch (Exception $e) {
    // In caso di errore, restituisce un JSON con il messaggio d'errore
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Chiude la connessione se è stata aperta
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>