<?php
// ============================================================
// config/firestore.php
// Helper para operaciones CRUD con Firestore REST API
// No requiere Composer ni paquetes externos — solo cURL (nativo en PHP)
// ============================================================
require_once __DIR__ . '/firebase_config.php';

class Firestore {

    // ── Utilidad: petición cURL a la API REST ──────────────────
    private static function request(string $url, string $method = 'GET',
                                    array $body = [], string $token = ''): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $token ? "Authorization: Bearer $token" : null,
            ]),
        ]);

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true) ?? [];
        $decoded['_http_code'] = $httpCode;
        return $decoded;
    }

    // ── Convertir array PHP → formato de campos Firestore ──────
    private static function toFirestoreFields(array $data): array {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_int($value))    $fields[$key] = ['integerValue'   => (string)$value];
            elseif (is_float($value)) $fields[$key] = ['doubleValue'  => $value];
            elseif (is_bool($value))  $fields[$key] = ['booleanValue' => $value];
            elseif (is_null($value))  $fields[$key] = ['nullValue'    => null];
            elseif (is_array($value)) $fields[$key] = ['stringValue'  => json_encode($value)];
            else                      $fields[$key] = ['stringValue'  => (string)$value];
        }
        return $fields;
    }

    // ── Convertir campos Firestore → array PHP ──────────────────
    public static function fromFirestoreFields(array $document): array {
        $result = [];
        $fields = $document['fields'] ?? [];

        foreach ($fields as $key => $typedValue) {
            $type  = array_key_first($typedValue);
            $value = $typedValue[$type];
            $result[$key] = match($type) {
                'integerValue'  => (int)$value,
                'doubleValue'   => (float)$value,
                'booleanValue'  => (bool)$value,
                'nullValue'     => null,
                default         => $value,
            };
        }

        // Agregar ID del documento (última parte del nombre)
        if (isset($document['name'])) {
            $parts = explode('/', $document['name']);
            $result['id'] = end($parts);
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────
    // CREATE — Agrega un documento con ID automático
    // ──────────────────────────────────────────────────────────
    public static function create(string $collection, array $data, string $token = ''): array {
        $url    = FIRESTORE_BASE_URL . '/' . $collection;
        $body   = ['fields' => self::toFirestoreFields($data)];
        $result = self::request($url, 'POST', $body, $token);

        return [
            'success' => isset($result['name']),
            'id'      => isset($result['name']) ? basename($result['name']) : null,
            'error'   => $result['error']['message'] ?? null,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // READ ONE — Lee un documento por ID
    // ──────────────────────────────────────────────────────────
    public static function get(string $collection, string $docId, string $token = ''): array {
        $url    = FIRESTORE_BASE_URL . '/' . $collection . '/' . $docId;
        $result = self::request($url, 'GET', [], $token);

        if (isset($result['error'])) {
            return ['success' => false, 'data' => null, 'error' => $result['error']['message']];
        }
        return ['success' => true, 'data' => self::fromFirestoreFields($result)];
    }

    // ──────────────────────────────────────────────────────────
    // READ ALL — Lista documentos de una colección
    // Soporta filtros simples: ['campo' => 'valor']
    // ──────────────────────────────────────────────────────────
    public static function getAll(string $collection, array $filters = [],
                                  int $limit = 50, string $token = ''): array
    {
        // Si hay filtros, usar runQuery (structured query)
        if (!empty($filters)) {
            return self::query($collection, $filters, $limit, $token);
        }

        $url    = FIRESTORE_BASE_URL . '/' . $collection . '?pageSize=' . $limit;
        $result = self::request($url, 'GET', [], $token);

        if (isset($result['error'])) {
            return ['success' => false, 'data' => [], 'error' => $result['error']['message']];
        }

        $docs = [];
        foreach ($result['documents'] ?? [] as $doc) {
            $docs[] = self::fromFirestoreFields($doc);
        }
        return ['success' => true, 'data' => $docs];
    }

    // ──────────────────────────────────────────────────────────
    // QUERY — Filtro con operadores (==, <, >, etc.)
    // ──────────────────────────────────────────────────────────
    public static function query(string $collection, array $filters,
                                 int $limit = 50, string $token = ''): array
    {
        $url = 'https://firestore.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID
             . '/databases/(default)/documents:runQuery';

        $fieldFilters = [];
        foreach ($filters as $field => $value) {
            $firestoreValue = is_int($value)
                ? ['integerValue' => (string)$value]
                : ['stringValue'  => $value];

            $fieldFilters[] = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $field],
                    'op'    => 'EQUAL',
                    'value' => $firestoreValue,
                ]
            ];
        }

        $where = count($fieldFilters) === 1
            ? $fieldFilters[0]
            : ['compositeFilter' => ['op' => 'AND', 'filters' => $fieldFilters]];

        $body = ['structuredQuery' => [
            'from'  => [['collectionId' => $collection]],
            'where' => $where,
            'limit' => $limit,
        ]];

        $result = self::request($url, 'POST', $body, $token);

        $docs = [];
        foreach ((array)$result as $item) {
            if (isset($item['document'])) {
                $docs[] = self::fromFirestoreFields($item['document']);
            }
        }
        return ['success' => true, 'data' => $docs];
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE — Actualiza campos específicos (PATCH / merge)
    // ──────────────────────────────────────────────────────────
    public static function update(string $collection, string $docId,
                                  array $data, string $token = ''): array
    {
        $fieldPaths = implode('&', array_map(
            fn($k) => 'updateMask.fieldPaths=' . urlencode($k),
            array_keys($data)
        ));
        $url  = FIRESTORE_BASE_URL . '/' . $collection . '/' . $docId . '?' . $fieldPaths;
        $body = ['fields' => self::toFirestoreFields($data)];
        $res  = self::request($url, 'PATCH', $body, $token);

        return [
            'success' => isset($res['name']),
            'error'   => $res['error']['message'] ?? null,
        ];
    }

    // ──────────────────────────────────────────────────────────
    // DELETE — Elimina un documento
    // ──────────────────────────────────────────────────────────
    public static function delete(string $collection, string $docId, string $token = ''): array {
        $url = FIRESTORE_BASE_URL . '/' . $collection . '/' . $docId;
        $res = self::request($url, 'DELETE', [], $token);
        return [
            'success' => ($res['_http_code'] === 200),
            'error'   => $res['error']['message'] ?? null,
        ];
    }
}
