<?php

header('Content-Type: application/json');

include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

try {
    // Legge il token dall'header
    $headers = apache_request_headers();

    if (!isset($headers['Authorization'])) {
        throw new Exception('Il dato richiesto (token) è mancante.');
    }

    $token = $headers['Authorization'];

    // Query per eliminare il token
    $query = "DELETE FROM Token WHERE token = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query: ' . $conn->error);
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Se il token è stato trovato e cancellato
        $response = [
            'success' => true,
            'message' => 'Token cancellato con successo',
        ];
    } else {
        // Se il token non è stato trovato o già cancellato
        throw new Exception('Token non trovato o già cancellato.');
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

?>