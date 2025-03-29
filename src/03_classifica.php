<?php
include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

header('Content-Type: application/json');

try {
    // Legge il token dall'header
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';

    $username = verifyToken($token);

    if (!$username) {
        throw new Exception('Token non valido.');
    }

    // Query per ottenere la classifica
    $query = "SELECT username, SUM(megabyte) AS total_score
              FROM Punteggio
              WHERE data >= CURDATE() - INTERVAL 7 DAY
              GROUP BY username
              ORDER BY total_score DESC
              LIMIT 10";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query: ' . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Errore nell\'esecuzione della query per la classifica.');
    }

    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception('Nessun punteggio trovato per la settimana.');
    }

    $stmt->bind_result($username, $score_string);

    $classifica = [];

    while ($stmt->fetch()) {
        $score_float = (float) $score_string;
        $co2 = calcolaCO2($score_float);  // Calcola il CO2 per il punteggio

        $classifica[] = [
            'username' => $username,
            'score' => $co2
        ];
    }

    // Restituisci la classifica in formato JSON
    echo json_encode([
        'success' => true,
        'classifica' => $classifica
    ]);
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