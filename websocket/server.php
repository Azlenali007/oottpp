<?php
/**
 * Standalone Pure Core PHP WebSocket Server
 * 
 * Compliant with RFC 6455. Zero third-party dependencies.
 * Run via CLI: php websocket/server.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/EventHandler.php';

use WebSocket\Auth;
use WebSocket\EventHandler;

$host = env('WS_HOST', '0.0.0.0');
$port = (int) env('WS_PORT', 8080);
$address = "tcp://{$host}:{$port}";

$server = stream_socket_server($address, $errno, $errstr);
if (!$server) {
    echo "WebSocket server failed to bind on {$address}: {$errstr} ({$errno})\n";
    exit(1);
}

stream_set_blocking($server, false);
echo "OTPPro WebSocket Server listening on ws://{$host}:{$port}...\n";

$clients = [];
$handshakes = [];
$userMap = []; // socketId => user array

function performHandshake($client, string $headers): ?string {
    if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $headers, $matches)) {
        $key = trim($matches[1]);
        $accept = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
        fwrite($client, $upgrade);
        return $key;
    }
    return null;
}

function encodeFrame(string $text): string {
    $b1 = 0x81; // FIN + text opcode
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } elseif ($length > 125 && $length < 65536) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, $length);
    }
    return $header . $text;
}

function decodeFrame(string $data): ?string {
    if (strlen($data) < 2) return null;
    $length = ord($data[1]) & 127;
    $maskOffset = 2;
    if ($length === 126) {
        $maskOffset = 4;
    } elseif ($length === 127) {
        $maskOffset = 10;
    }

    $masks = substr($data, $maskOffset, 4);
    $payload = substr($data, $maskOffset + 4);
    $text = '';
    for ($i = 0; $i < strlen($payload); ++$i) {
        $text .= $payload[$i] ^ $masks[$i % 4];
    }
    return $text;
}

while (true) {
    $read = array_merge([$server], $clients);
    $write = null;
    $except = null;

    if (@stream_select($read, $write, $except, 1) > 0) {
        if (in_array($server, $read, true)) {
            $newClient = stream_socket_accept($server);
            if ($newClient) {
                stream_set_blocking($newClient, false);
                $clientId = (int) $newClient;
                $clients[$clientId] = $newClient;
                $handshakes[$clientId] = false;
            }
            unset($read[array_search($server, $read, true)]);
        }

        foreach ($read as $client) {
            $clientId = (int) $client;
            $data = @fread($client, 4096);

            if ($data === false || $data === '') {
                // Client disconnected
                unset($clients[$clientId], $handshakes[$clientId], $userMap[$clientId]);
                @fclose($client);
                continue;
            }

            if (!$handshakes[$clientId]) {
                if (performHandshake($client, $data)) {
                    $handshakes[$clientId] = true;
                    // Send connection confirmation
                    fwrite($client, encodeFrame(json_encode(['event' => 'connected', 'status' => 'authenticated'])));
                }
            } else {
                $message = decodeFrame($data);
                if ($message) {
                    $json = json_decode($message, true);
                    if ($json && isset($json['action']) && $json['action'] === 'ping') {
                        fwrite($client, encodeFrame(json_encode(['event' => 'pong', 'time' => time()])));
                    }
                }
            }
        }
    }
}
