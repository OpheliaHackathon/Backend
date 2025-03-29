<?php
include 'Handler/connessione_database.php';
include 'Handler/funzioni.php';

header('Content-Type: application/json');

define('OPENAI_API_KEY', 'chiave_qui');

try {
    // Verifica il token dall'header
    $token = apache_request_headers()['Authorization'] ?? '';
    $username = verifyToken($token);

    if (!$username) {
        throw new Exception('Token non valido o non presente.');
    }

    // Verifica il consiglio esistente nelle ultime 24 ore
    $queryConsiglio = "SELECT consiglio, ultimo_consiglio FROM Utente WHERE username = ?";
    $stmtConsiglio = $conn->prepare($queryConsiglio);
    $stmtConsiglio->bind_param("s", $username);
    $stmtConsiglio->execute();
    $consiglioEsistente = $stmtConsiglio->get_result()->fetch_assoc();
    $stmtConsiglio->close();

    $consiglioRecente = false;
    if ($consiglioEsistente && $consiglioEsistente['consiglio'] && $consiglioEsistente['ultimo_consiglio']) {
        $ultimoConsiglio = new DateTime($consiglioEsistente['ultimo_consiglio']);
        $oreTrascorse = (new DateTime())->diff($ultimoConsiglio)->h + ((new DateTime())->diff($ultimoConsiglio)->days * 24);
        $consiglioRecente = $oreTrascorse < 24;
    }

    // Recupera l'utilizzo settimanale dell'utente
    $query = "SELECT data, megabyte FROM Punteggio WHERE username = ? AND data >= CURDATE() - INTERVAL 7 DAY ORDER BY data DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $weeklyUsage = [];
    $totalCO2 = 0;
    while ($row = $result->fetch_assoc()) {
        $co2 = calcolaCO2($row['megabyte']);
        $totalCO2 += $co2;
        $weeklyUsage[] = ['data' => $row['data'], 'co2' => $co2];
    }

    // Risposta se non ci sono dati sufficienti
    if (empty($weeklyUsage)) {
        echo json_encode([
            'success' => true,
            'message' => 'Non ci sono sufficienti dati di utilizzo per generare consigli.',
            'consiglio' => 'Inizia a utilizzare la nostra estensione per tracciare la tua impronta digitale.'
        ]);
        exit;
    }

    // Se c'è un consiglio recente, lo restituisco
    if ($consiglioRecente) {
        echo json_encode([
            'success' => true,
            'utilizzo_settimanale' => $weeklyUsage,
            'co2_totale' => $totalCO2,
            'consiglio' => $consiglioEsistente['consiglio'],
            'ultimo_consiglio' => $consiglioEsistente['ultimo_consiglio'],
            'cached' => true
        ]);
    } else {
        // Genera un nuovo consiglio
        $prompt = "L'utente ha generato {$totalCO2} grammi di CO2 negli ultimi 7 giorni. Fornisci 1 consiglio breve per ridurre la sua impronta digitale.";
        $consiglio = callOpenAI($prompt);

        if ($consiglio) {
            $dataOra = date('Y-m-d H:i:s');
            // Aggiorna il consiglio nel database
            $queryUpdate = "UPDATE Utente SET consiglio = ?, ultimo_consiglio = ? WHERE username = ?";
            $stmtUpdate = $conn->prepare($queryUpdate);
            $stmtUpdate->bind_param("sss", $consiglio, $dataOra, $username);
            $stmtUpdate->execute();

            if ($stmtUpdate->affected_rows == 0) {
                throw new Exception('Impossibile aggiornare il consiglio.');
            }

            echo json_encode([
                'success' => true,
                'utilizzo_settimanale' => $weeklyUsage,
                'co2_totale' => $totalCO2,
                'consiglio' => $consiglio,
                'ultimo_consiglio' => $dataOra,
                'cached' => false
            ]);
        } else {
            throw new Exception('Errore nella generazione dei consigli.');
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt))
        $stmt->close();
    if (isset($conn))
        $conn->close();
}

// Funzione per chiamare l'API di OpenAI
function callOpenAI($prompt)
{
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'Sei un consulente esperto di sostenibilità ambientale. Fornisci consigli brevi e pratici.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 150,
        'temperature' => 0.7
    ];
    $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $responseArray = json_decode($response, true);
        return $responseArray['choices'][0]['message']['content'] ?? 'Non è stato possibile generare consigli personalizzati.';
    }

    return null;
}
?>