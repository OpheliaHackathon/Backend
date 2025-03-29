<?php
include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

header('Content-Type: application/json');

try {
    // Legge il JSON ricevuto
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    // Verifica che il JSON sia stato decodificato correttamente
    if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Errore nella codifica dei dati inseriti nel JSON.');
    }

    // Verifica che i dati siano stati salvati correttamente
    if (!isset($data['usage'], $data['date'])) {
        throw new Exception('Dati mancanti: usage o date.');
    }

    $usage = $data['usage'];
    $date = $data['date'];

    // Legge il token dall'header
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';

    // Verifica che il token sia presente e valido
    $username = verifyToken($token);
    if (!$username) {
        throw new Exception('Token non valido o non trovato.');
    }

    // Query per inserire i dati nella tabella Punteggio
    $query = "INSERT INTO Punteggio (username, data, megabyte)
              VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE
              megabyte = megabyte + VALUES(megabyte)";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Errore nella preparazione della query: ' . $conn->error);
    }

    $stmt->bind_param("sss", $username, $date, $usage);
    if (!$stmt->execute()) {
        throw new Exception('Errore nell\'esecuzione della query: ' . $stmt->error);
    }

    echo json_encode(['success' => true]);

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