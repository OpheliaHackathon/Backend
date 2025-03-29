<?php

header('Content-Type: application/json');

include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

try {
    // Legge il token dall'header
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';

    $username = verifyToken($token);

    // Verifica se il token è valido
    if (!$username) {
        throw new Exception('Errore nella validazione del token, token non presente nel database.');
    }


    $mode = $_GET["mode"];

    switch ($mode) {
        case '1':
            // Calcolare il giorno della settimana per oggi (1 = Lunedì, 7 = Domenica)
            $dayOfWeek = (int) date('N');

            // Trova la data della domenica precedente
            $sundayPrevious = date('Y-m-d', strtotime('last sunday'));

            // Calcolare la query per ottenere i dati da domenica precedente a oggi
            $query = "
                SELECT megabyte, data 
                FROM Punteggio 
                WHERE username = ?
                AND data >= ? -- Data della domenica precedente
                AND data <= CURDATE() -- Fino alla data di oggi
                ORDER BY data DESC
            ";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Errore nella preparazione della query: ' . $conn->error);
            }

            // Passiamo i parametri: username e la data della domenica precedente
            $stmt->bind_param("ss", $username, $sundayPrevious);
            $stmt->execute();
            $stmt->store_result();

            $week = array_fill(0, 7, 0); // Inizializza l'array della settimana con 0, 7 giorni

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($megabyte, $data);

                // Popola i dati della settimana
                while ($stmt->fetch()) {
                    // Calcolare l'indice del giorno della settimana
                    $dayIndex = (int) date('N', strtotime($data));

                    // Posizionare il valore del megabyte al giusto giorno della settimana (0 = domenica, 1 = lunedì, ...)
                    $week[$dayIndex - 1] = calcolaCO2($megabyte);
                }
            }

            // Imposta il valore per "daily" (CO2 per oggi)
            $daily = $week[$dayOfWeek - 1]; // Oggi è il giorno corrente

            echo json_encode([
                'daily' => $daily,  // CO2 per la giornata odierna
                'week' => $week,    // CO2 per la settimana, con 0 per i giorni senza dati
            ]);

            break;


        case '2':
            $query = "SELECT megabyte, data 
                FROM Punteggio 
                WHERE username = ?
                AND data >= DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) + 6) DAY)  
                AND data < DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)    
                ORDER BY data DESC";

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Errore nella preparazione della query: ' . $conn->error);
            }

            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            $week = [];
            $daily = 0;

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($megabyte, $data);

                while ($stmt->fetch()) {
                    $co2 = calcolaCO2($megabyte);
                    $week[] = $co2;
                }

                // L'ultimo giorno della settimana precedente
                $daily = $week[0] ?? 0;
            }

            echo json_encode([
                'daily' => $daily,
                'week' => $week,
            ]);

            break;

        default:
            throw new Exception('Modalità non valida.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
} finally {
    // Chiusura della connessione al database per evitare memory leak
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>