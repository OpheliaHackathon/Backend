<?php

define('TOKEN_LENGTH', 32);  // Costante per definire la lunghezza del token
/*funzione per generare il token*/
function generateToken()
{
    $bytes = random_bytes(TOKEN_LENGTH / 2);
    return bin2hex($bytes);
}

/*funzione per verificare il token*/
function verifyToken($token)
{
    include 'connessione_database.php';
    if (empty($token)) {
        echo json_encode([
            'success' => false,
            'message' => 'Token mancante'
        ]);
        return null;
    }

    // Query per recuperare l'username dal token
    $query = "SELECT username FROM Token WHERE token = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Errore nella preparazione della query: ' . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("s", $token);

    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows > 0) {

            $stmt->bind_result($username);
            $stmt->fetch();
            return $username;
        } else {
            return null;
        }
    }

}

/*funzione per calcolar il numero di chilometri che una macchina effettuerebbe con lo stesso valore di CO2*/
function calcolaCO2($megabyte)
{
    // CO2 prodotta

    $co2_grammi = $megabyte * 0.0002 * 430;

    return $co2_grammi;
}
?>